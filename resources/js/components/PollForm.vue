<script setup>
import { ref } from "vue";
import { useFetchApi } from "@/composables/useFetchApi";

//props pour sondage (null si création, obj si edit)
const props = defineProps({
    poll: { type: Object, default: null },
});

//émet events au parent (AppPollDashboard) pour save ou cancel
const emit = defineEmits(["saved", "cancel"]);

//faire appel API (appel backend depuis formulaire)
const { fetchApi } = useFetchApi();

//champ du form existants ou vide
const question = ref(props.poll?.question ?? "");
const title = ref(pops.poll?.title ?? "");
const allowMultipleChoices = ref(props.poll?.allow_multiple_choices ?? false);
const resultsPublic = ref(props.poll?.results_public ?? false);
const duration = ref(props.poll?.duration ?? "");
const options = ref(props.poll?.options ?? []);
//état du formulaire (nv option, erreur, edit option, etc)
const newOptionLabel = ref("");
const error = ref("");
const editingOptionId = ref(null);
const editingLabel = ref("");
const startNow = ref(false);

async function save() {
    //valid cote client
    if (!question.value) {
        error.value = "La question est obligatoire.";
        return;
    }

    //obj à envoyer au backend (API)
    const data = {
        question: question.value,
        title: title.value,
        allow_multiple_choices: allowMultipleChoices.value,
        results_public: resultsPublic.value,
        duration: duration.value || null,
    };

    try {
        let savedPoll;
        //si existe -> modif
        if (props.poll) {
            savedPoll = await fetchApi({
                url: "polls/" + props.poll.id,
                method: "PUT",
                data,
            });
        //sinon -> création
        } else {
            savedPoll = await fetchApi({ url: "polls", method: "POST", data });
        }
        //si création et StartNow -> lancer sondage
        if (!props.poll && startNow.value) {
            savedPoll = await fetchApi({
                url: "polls/" + savedPoll.id + "/start",
                method: "POST",
            });
        }
        //émet event saved au parent (AppPollDashboard) avec sondage cree ou modif
        emit("saved", savedPoll);
    } catch (err) {
        error.value = "Une erreur est survenue.";
    }
}

async function addOption() {
    if (!newOptionLabel.value) return; //champ vide -> inutile appeler API
    //appel API ajout option & stockée
    try {
        const option = await fetchApi({
            url: "polls/" + props.poll.id + "/options",
            method: "POST",
            data: { label: newOptionLabel.value },
        });
        options.value.push(option);
        newOptionLabel.value = "";
    } catch (err) {
        error.value = "Erreur lors de l'ajout de l'option.";
    }
}

async function removeOption(optionId) {
    try {
        await fetchApi({
            url: "polls/" + props.poll.id + "/options/" + optionId,
            method: "DELETE",
        });
        //reconstruction sans option supp
        options.value = options.value.filter((o) => o.id !== optionId);
    } catch (err) {
        error.value = "Erreur lors de la suppression.";
    }
}

async function saveOption(optionId) {
    try {
        const updated = await fetchApi({
            url: "polls/" + props.poll.id + "/options/" + optionId,
            method: "PUT",
            data: { label: editingLabel.value },
        });
        const index = options.value.findIndex((o) => o.id === optionId);
        if (index !== -1) options.value[index] = updated; //remplace option modif si trouvée
        editingOptionId.value = null; //remet à null -> ferme edit
    } catch (err) {
        error.value = "Erreur lors de la modification.";
    }
}
</script>

<template>
    <div class="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold mb-6">
            {{ poll ? "Modifier le sondage" : "Nouveau sondage" }}
        </h2>

        <!-- verif que erreur est vide (rien persistant) -->
        <p v-if="error" class="text-red-500 mb-4">{{ error }}</p>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1"
                >Titre (optionnel)</label
            >
            <input
                v-model="title"
                type="text"
                placeholder="Titre"
                class="border border-slate-300 rounded-md px-3 py-2 w-full"
            />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1"
                >Question *</label
            >
            <input
                v-model="question"
                type="text"
                placeholder="Votre question"
                class="border border-slate-300 rounded-md px-3 py-2 w-full"
            />
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-2">
                <input v-model="allowMultipleChoices" type="checkbox" />
                <span class="text-sm text-slate-700">Choix multiple</span>
            </label>
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-2">
                <input v-model="resultsPublic" type="checkbox" />
                <span class="text-sm text-slate-700">Résultats publics</span>
            </label>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1"
                >Durée (secondes, optionnel)</label
            >
            <input
                v-model="duration"
                type="number"
                min="1"
                placeholder="Ex: 3600"
                class="border border-slate-300 rounded-md px-3 py-2 w-full"
            />
        </div>

        <!-- que en mode edit evidemment -->
        <div v-if="poll" class="mb-6">
            <h3 class="text-lg font-semibold mb-3">Options</h3>
            <div
                v-for="option in options"
                :key="option.id"
                class="flex items-center justify-between border border-slate-200 rounded-md px-3 py-2 mb-2"
            >
                <!-- pr afficher mode edit si option cours edit -->
                <div
                    v-if="editingOptionId === option.id"
                    class="flex gap-2 flex-1"
                >
                    <!-- label de option en cours edit -->
                    <input
                        v-model="editingLabel"
                        type="text"
                        class="border border-slate-300 rounded-md px-3 py-1 flex-1"
                    />
                    <button
                        @click="saveOption(option.id)"
                        class="bg-teal-600 text-white px-2 py-1 rounded text-sm hover:bg-teal-700"
                    >
                        OK
                    </button>
                    <!-- remet à null -> ferme edit -->
                    <button
                        @click="editingOptionId = null"
                        class="bg-slate-200 text-slate-700 px-2 py-1 rounded text-sm hover:bg-slate-300"
                    >
                        Annuler
                    </button>
                </div>
                <!-- si pas en cours d'edit (affichage normal)-->
                <div v-else class="flex items-center justify-between w-full">
                    <span>{{ option.label }}</span>
                    <div class="flex gap-2">
                        <!-- pour stocker id option en modif et label préremplis-->
                        <button
                            @click="
                                editingOptionId = option.id;
                                editingLabel = option.label;
                            "
                            class="bg-slate-200 text-slate-700 px-2 py-1 rounded text-sm hover:bg-slate-300"
                        >
                            Modifier
                        </button>
                        <button
                            @click="removeOption(option.id)"
                            class="bg-red-500 text-white px-2 py-1 rounded text-sm hover:bg-red-600"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
            <!--nv option-->
            <div class="flex gap-2 mt-2">
                <input
                    v-model="newOptionLabel"
                    type="text"
                    placeholder="Nouvelle option"
                    class="border border-slate-300 rounded-md px-3 py-2 flex-1"
                />
                <button
                    @click="addOption"
                    class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700"
                >
                    Ajouter
                </button>
            </div>
        </div>

        <p v-else class="text-slate-500 text-sm mb-6">
            Sauvegarde d'abord le sondage pour pouvoir ajouter des options.
        </p>
        <!-- affiché que mode création -->
        <div v-if="!poll" class="mb-4">
            <label class="flex items-center gap-2">
                <input v-model="startNow" type="checkbox" />
                <span class="text-sm text-slate-700"
                    >Démarrer immédiatement</span
                >
            </label>
        </div>

        <div class="flex gap-3">
            <!-- btn savec (PUT/POST selon mode (props.poll dans save()))-->
            <button
                @click="save"
                class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700"
            >
                Enregistrer
            </button>
            <!-- émet cancel au parent -> ferme form-->
            <button
                @click="emit('cancel')"
                class="bg-slate-200 text-slate-700 px-4 py-2 rounded-md hover:bg-slate-300"
            >
                Annuler
            </button>
        </div>
    </div>
</template>
