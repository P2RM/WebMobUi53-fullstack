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

const { polls, setPolls } = usePollStore();
setPolls(props.polls);

const showForm = ref(false);
const currentPoll = ref(null);

function openCreate() {
    currentPoll.value = null;
    showForm.value = true;
}

function openEdit(poll) {
    currentPoll.value = poll;
    showForm.value = true;
}

function onSaved(savedPoll) {
    const index = polls.value.findIndex((p) => p.id === savedPoll.id);
    if (index !== -1) {
        polls.value[index] = savedPoll;
    } else {
        polls.value.unshift(savedPoll);
    }
    showForm.value = false;
}
</script>

<template>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div v-if="showForm">
            <PollForm
                :poll="currentPoll"
                @saved="onSaved"
                @cancel="showForm = false"
            />
        </div>

        <div v-else>
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold">Mes sondages</h1>
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
