<script setup>
import { ref } from "vue";
import PollTable from "./components/PollTable.vue";
import PollForm from "./components/PollForm.vue";
import { usePollStore } from "@/stores/usePollStore";

const props = defineProps({
    polls: { type: Array, default: () => [] },
    loginUrl: { type: String, default: null },
    username: { type: String, default: null },
});

//récupère sondages depui store (pas requete API inutile vu existe déjà en PHP)
const { polls, setPolls } = usePollStore();
setPolls(props.polls); //stock sondage dans polls du store

const showForm = ref(false); //affiche tableau sondage ou form création/edit (boolean dans template)
const currentPoll = ref(null); //sondage en cours d'edit (null si création)

function openCreate() { //remet sondage à null et showForm true pour création (false = tableau)
    currentPoll.value = null;
    showForm.value = true;
}

function openEdit(poll) { //récupère sondage à edit et showForm true pour création (false = tableau)
    currentPoll.value = poll;
    showForm.value = true;
}

function onSaved(savedPoll) {
    //soit on le remplace si modif (-1) sinon ajoute au début
    const index = polls.value.findIndex((p) => p.id === savedPoll.id);
    if (index !== -1) {
        polls.value[index] = savedPoll;
    } else {
        polls.value.unshift(savedPoll);
    }
    showForm.value = false; //ferme formulaire
}
</script>

<template>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <!-- form (que si true)-->
        <div v-if="showForm">
            <!-- passe sondage (null créa, obj edit) -->
            <PollForm
                :poll="currentPoll"
                @saved="onSaved"
                @cancel="showForm = false"
            />
        </div>

        <!-- tab (que si false)-->
        <div v-else>
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold">Mes sondages</h1>
                <!-- currentPoll = null & showForm = true -->
                <button
                    @click="openCreate"
                    class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700"
                >
                    Nouveau sondage
                </button>
            </div>
            <PollTable @edit="openEdit" />
        </div>
    </div>
</template>
