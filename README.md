HEIG-VD WebMobUI — Système de sondages

Fonctionnalités implémentées :

- Créer, modifier et supprimer ses sondages depuis un dashboard personnel
- Définir la question, les options de réponse et les paramètres (choix simple ou multiple, résultats publics, durée)
- Créer un sondage en mode brouillon, puis le démarrer immédiatement ou plus tard
- Obtenir le lien de partage via un bouton "Copier le lien"
- Voter depuis une page publique accessible via un lien contenant un token
- Résultats en direct avec barres de progression mises à jour automatiquement
- Vote bloqué après la date de fin, état affiché clairement
- Résultats visibles uniquement si publics, sauf pour le créateur qui les voit toujours
- Unicité du vote pour les sondages à choix unique, côté API et côté frontend


Parcours de développement :

Modèles et données :

La première étape a été de compléter les modèles Poll, PollOption et PollVote en ajoutant $fillable à chacun. Sans ça, Laravel refuse toute création ou mise à jour par tableau — protection contre les injections de masse — ce qui rendrait impossible d'utiliser Poll::create([...]) ou $poll->update([...]) dans les controllers.

Construction de l'API & routes :

Le point centrale était de séparer ce qui doit être accessible publiquement de ce qui nécessite une connexion. La page de vote doit pouvoir être ouverte par n'importe qui qui a lien, donc GET /api/v1/polls/{token} est déclaré hors du groupe auth:sanctum. Tout le reste (créer, modifier, supprimer, voter, gérer les options) est protégé.
Les sondages sont identifiés par un token aléatoire de 32 caractères plutôt que par l'ID numérique dans l'URL de vote. Les IDs sont séquentiels ce qui permettrait d'énumérer tous les sondages. Avec un token, le lien de partage est le seul moyen d'accéder au sondage.

ApiPollController :

Pour index(), ->with('options') charge les options de chaque sondage en une seule requête SQL plutôt qu'une par sondage.
Pour store(), un bug est apparu : quand "Démarrer immédiatement" est coché, le frontend enchaîne la création puis /start sans repasser par un PUT. Si la durée n'est pas enregistré à la création, /start trouve $poll->duration = null et ne calcule jamais ends_at. duration, allow_multiple_choices et results_public sont donc inclus direct dans store().
Pour update(), la règle sometimes|required sur les champs optionnels permet des màj partielles, on peut envoyer que les champs modifiés sans renvoyer tout le poll.

start() calcule ends_at = now() + duration au moment du démarrage, pas à la création. Si c'était calculé à la création, un sondage laissé en brouillon deux jours serait déjà presque expiré avant d'avoir été lancé. start() retourne le poll avec $poll->load('options'), sans ça le frontend recevait un objet sans le tableau options, ce qui écrasait les options déjà affichées dans le dashboard.

ApiPollOptionController et ApiPollVoteController :

J'ai fait un controller dédié aux options plutôt que des méthodes supplémentaires dans ApiPollController, pour garder chaque controller pour sur une seule ressource. Chaque méthode fait une double vérification : le sondage appartient au usr connecté et l'option appartient bien à ce sondage (ce qui empêche par ex de supprimer l'option d'un sondage qui ne nous appartient pas, même en connaissant de l'id).
Pour le vote, les conditions sont vérifiées dans l'ordre -> sondage existant, non en brouillon, non expiré, option valide, et si allow_multiple_choices = false, absence de vote précédent. ->exists() plutôt que ->count() pour la vérification de doublon (booléen).

Bug routing (/polls/dashboard capturé comme token) : 

La route web /polls/{token} était déclarée dans le groupe auth, et Laravel l'exécutait avant la route du dashboard, capturant "dashboard" comme valeur du paramètre {token}. Donc accéder à /polls/dashboard affichait "Sondage introuvable". J'ai donc déplacer /polls/{token} avant le groupe auth dans routes/web.php. Cas concret où l'ordre de déclaration change complètement le comportement de l'application. 

Architecture frontend :

Chaque page a son propre point d'entrée Vite (poll-dashboard.js, poll-vote.js) déclaré dans vite.config.js, ce qui génère deux bundles : le dashboard ne charge pas le code de la page de vote et vice versa. Les données initiales sont passées depuis PHP via un attribut data-props en JSON dans le HTML, lu au montage avec JSON.parse(el.dataset.props).

État partagé (usePollStore) :

AppPollDashboard et PollTable partagent la même liste de sondages. Faire descendre la liste par props et remonter chaque modification par événements aurait fait beaucoup de couplage. Solution : un ref déclaré au niveau du module dans usePollStore.js. Comme les modules sont des singletons, tous les composants qui importent ce fichier partagent la même référence.

PollForm :

PollForm gère les deux modes selon la prop poll : si null, création (POST) et si un objet, édition (PUT). La section de gestion des options est masquée avec v-if="poll" en mode création, car on n'a pas encore d'ID pour appeler /options. La case "Démarrer immédiatement" n'apparaît qu'en création avec v-if="!poll".
Un bug est apparu -> la première version de onSaved utilisait if (currentPoll.value) pour différencier création et édition. Avec "créer + démarrer immédiatement", le sondage apparaissait parfois deux fois dans la liste. Remplacé par un findIndex : si le sondage existe déjà par son id, on le met à jour ; sinon, on l'ajoute.

Page de vote (AppPollVote) :

Le sondage est chargé avec GET /api/v1/polls/{token} au montage et rafraîchi toutes les 5 secondes avec usePolling pour maintenir les résultats à jour. La taille des barres de progression est calculée avec percentage(option).
Pour l'unicité, la vérification API seule ne suffit pas -> après un vote si l'utilisateur recharge la page, le formulaire réapparaissait car le ref hasVoted était réinitialisé. hasVoted est donc persisté dans le localStorage via useJsonStorage, avec une clé unique par sondage (voted_{token}).
Pour les sondages à choix multiple le backend accepte un seul poll_option_id par requête. Plutôt que de modifier l'API pour accepter un tableau, une requête est envoyée par option dans une boucle for...of. Côté template, <input type="checkbox"> si allow_multiple_choices sinon <input type="radio">.

Visibilité des résultats :

Au début la condition v-if="poll.results_public" empêchait le créateur de voir les résultats de son propre sondage si la visibilité était privée. J'ai ajouté un champ is_owner dans la réponse de GET /api/v1/polls/{token} calculé avec auth('sanctum')->user() qui fonctionne sur une route publique sans la rendre protégée. La condition devient poll.results_public || poll.is_owner.
