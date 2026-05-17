<script setup>
import { ref } from "vue";
import { useFetchApi } from "@/composables/useFetchApi";
import { usePolling } from "@/composables/usePolling";
import { useJsonStorage } from "@/composables/useJsonStorage";

const props = defineProps({
    token: { type: String, required: true },
});

const { fetchApi } = useFetchApi();

const poll = ref(null);
const error = ref("");
const selectedOptionId = ref(null);
const selectedOptionIds = ref([]);
const { data: hasVoted } = useJsonStorage("voted_" + props.token, false);
const voteError = ref("");

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
        ? selectedOptionIds.value
        : selectedOptionId.value
          ? [selectedOptionId.value]
          : [];

    if (ids.length === 0) {
        voteError.value = "Sélectionne au moins une option.";
        return;
    }
    try {
        for (const id of ids) {
            await fetchApi({
                url: "polls/" + props.token + "/vote",
                method: "POST",
                data: { poll_option_id: id },
            });
        }
        hasVoted.value = true;
        voteError.value = "";
        await loadPoll();
    } catch (err) {
        if (err.status === 403) {
            voteError.value = err.data?.message ?? "Vote refusé.";
        } else {
            voteError.value = "Une erreur est survenue.";
        }
    }
}

function totalVotes() {
    if (!poll.value?.options) return 0;
    return poll.value.options.reduce((sum, o) => sum + o.votes_count, 0);
}

function percentage(option) {
    const total = totalVotes();
    if (total === 0) return 0;
    return Math.round((option.votes_count / total) * 100);
}

loadPoll();
usePolling(loadPoll, 5000);
</script>

<template>
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div v-if="error">
            <p class="text-red-500">{{ error }}</p>
        </div>

        <div v-else-if="poll" class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-2">
                {{ poll.title || poll.question }}
            </h1>
            <p v-if="poll.title" class="text-slate-500 mb-4">
                {{ poll.question }}
            </p>

            <p v-if="poll.is_draft" class="text-orange-500">
                Ce sondage n'est pas encore lancé.
            </p>

            <p
                v-else-if="poll.ends_at && new Date() > new Date(poll.ends_at)"
                class="text-red-500 font-semibold"
            >
                Ce sondage est terminé.
            </p>

            <div v-else>
                <p v-if="voteError" class="text-red-500 mb-4">
                    {{ voteError }}
                </p>

                <div v-if="!hasVoted" class="mb-6">
                    <div
                        v-for="option in poll.options"
                        :key="option.id"
                        class="mb-2"
                    >
                        <label class="flex items-center gap-2">
                            <input
                                v-if="poll.allow_multiple_choices"
                                type="checkbox"
                                :value="option.id"
                                v-model="selectedOptionIds"
                            />
                            <input
                                v-else
                                type="radio"
                                :value="option.id"
                                v-model="selectedOptionId"
                            />
                            <span>{{ option.label }}</span>
                        </label>
                    </div>
                    <button
                        @click="vote"
                        class="mt-4 bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700"
                    >
                        Voter
                    </button>
                </div>

                <p v-else class="text-teal-600 font-semibold mb-6">
                    Vote enregistré !
                </p>
            </div>

            <div
                v-if="poll.results_public || poll.is_owner"
                class="mt-6 border-t border-slate-200 pt-4"
            >
                <h2 class="text-lg font-semibold mb-3">Résultats</h2>
                <p class="text-slate-500 text-sm mb-4">
                    Total : {{ totalVotes() }} vote(s)
                </p>
                <div
                    v-for="option in poll.options"
                    :key="option.id"
                    class="mb-4"
                >
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ option.label }}</span>
                        <span class="text-slate-500"
                            >{{ option.votes_count }} ({{
                                percentage(option)
                            }}%)</span
                        >
                    </div>
                    <div class="bg-slate-200 rounded-full h-4 w-full">
                        <div
                            :style="{ width: percentage(option) + '%' }"
                            class="bg-teal-600 h-4 rounded-full transition-all"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else>
            <p class="text-slate-500">Chargement...</p>
        </div>
    </div>
</template>
