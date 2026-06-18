<script setup>
import { ref } from "vue";
import { useFetchApi } from "@/composables/useFetchApi";
import { usePolling } from "@/composables/usePolling";
import { useJsonStorage } from "@/composables/useJsonStorage";

//!!OBLIGATOIRE (token pr sondage)
const props = defineProps({
    token: { type: String, required: true },
});

const { fetchApi } = useFetchApi();

const poll = ref(null);
const error = ref(""); //poll introuvable
const selectedOptionId = ref(null); //solo
const selectedOptionIds = ref([]); //multi
const { data: hasVoted } = useJsonStorage("voted_" + props.token, false); //usr = 1 vote (true si dja)
const voteError = ref(""); //lors vote

async function loadPoll() {
    try {
        poll.value = await fetchApi({
            url: "polls/" + props.token,
            method: "GET",
        });
    } catch (err) {
        error.value = "Sondage introuvable.";
    }
}

async function vote() {
    const ids = poll.value.allow_multiple_choices
        ? selectedOptionIds.value //multi
        : selectedOptionId.value //solo
          ? [selectedOptionId.value] //save choix
          : []; //si aucun choix (éviter de voter vide) géré en dessous

    if (ids.length === 0) {
        voteError.value = "Sélectionne au moins une option.";
        return;
    }
    try {
        //envoie des votes au backend (boucle car accepte pas tab)
        for (const id of ids) {
            await fetchApi({
                url: "polls/" + props.token + "/vote",
                method: "POST",
                data: { poll_option_id: id },
            });
        }
        hasVoted.value = true; //cacher form vote
        voteError.value = ""; //reset msg erreur
        await loadPoll(); //reload poll pour maj votes
    } catch (err) {
        if (err.status === 403) { //deja voté, sondage closed, etc
            voteError.value = err.data?.message ?? "Vote refusé.";
        } else { //autre
            voteError.value = "Une erreur est survenue.";
        }
    }
}

function totalVotes() {
    if (!poll.value?.options) return 0; //si null ou pas chargé
    return poll.value.options.reduce((sum, o) => sum + o.votes_count, 0); //total
}

//% des options
function percentage(option) {
    const total = totalVotes();
    if (total === 0) return 0;
    return Math.round((option.votes_count / total) * 100);
}

loadPoll(); //1e fois au montage
usePolling(loadPoll, 5000); //tte les 5 sec
</script>

<template>
    <!-- gest erreur -->
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div v-if="error">
            <p class="text-red-500">{{ error }}</p>
        </div>
        <!-- pas erreur & poll != null -->
        <div v-else-if="poll" class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-2">
                {{ poll.title || poll.question }} <!-- si pas titre -> question-->
            </h1>
            <p v-if="poll.title" class="text-slate-500 mb-4">
                {{ poll.question }} <!-- si titre -> question en dessous-->
            </p>
            <!-- si brouillon-->
            <p v-if="poll.is_draft" class="text-orange-500">
                Ce sondage n'est pas encore lancé.
            </p>
            <!-- si terminé (new Date obj pour comparer)-->
            <p
                v-else-if="poll.ends_at && new Date() > new Date(poll.ends_at)"
                class="text-red-500 font-semibold"
            >
                Ce sondage est terminé.
            </p>
            <!-- en cours -> affichage normal-->
            <div v-else>

                <p v-if="voteError" class="text-red-500 mb-4">
                    {{ voteError }}
                </p>
                <!-- affichage options si a pas voté-->
                <div v-if="!hasVoted" class="mb-6">
                    <!-- boucle options -->
                    <div
                        v-for="option in poll.options"
                        :key="option.id"
                        class="mb-2"
                    >
                        <label class="flex items-center gap-2">
                            <!-- ajout dynamique des votes multiples -->
                            <input
                                v-if="poll.allow_multiple_choices"
                                type="checkbox"
                                :value="option.id"
                                v-model="selectedOptionIds"
                            />
                            <!-- si choix unique (décoche si sélectionne autre option)-->
                            <input
                                v-else
                                type="radio"
                                :value="option.id"
                                v-model="selectedOptionId"
                            />
                            <span>{{ option.label }}</span> <!-- affichage du label à coté option -->
                        </label>
                    </div>
                    <button
                        @click="vote"
                        class="mt-4 bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700"
                    >
                        Voter
                    </button>
                </div>
                <!-- affichage msg si a voté -->
                <p v-else class="text-teal-600 font-semibold mb-6">
                    Vote enregistré !
                </p>
            </div>
            <!-- visibilité résultats (privé/public)-->
            <div
                v-if="poll.results_public || poll.is_owner"
                class="mt-6 border-t border-slate-200 pt-4"
            >
                <h2 class="text-lg font-semibold mb-3">Résultats</h2>
                <p class="text-slate-500 text-sm mb-4">
                    Total : {{ totalVotes() }} vote(s)
                </p>
                <!-- boucle sur options pour afficher graph -->
                <div
                    v-for="option in poll.options"
                    :key="option.id"
                    class="mb-4"
                >
                    <div class="flex justify-between text-sm mb-1">
                        <!-- label + nb votes + % -->
                        <span>{{ option.label }}</span>
                        <span class="text-slate-500"
                            >{{ option.votes_count }} ({{
                                percentage(option)
                            }}%)</span
                        >
                    </div>
                    <!-- graph -->
                    <div class="bg-slate-200 rounded-full h-4 w-full">
                        <div
                            :style="{ width: percentage(option) + '%' }"
                            class="bg-teal-600 h-4 rounded-full transition-all"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- else si sondage null (chargement en attendant réponse API)-->
        <div v-else>
            <p class="text-slate-500">Chargement...</p>
        </div>
    </div>
</template>
