<script setup>
import { usePollStore } from "@/stores/usePollStore";
import { useFetchApi } from "@/composables/useFetchApi";

const emit = defineEmits(["edit"]);
const { polls, deletePoll } = usePollStore();
const { fetchApi } = useFetchApi();

async function delPoll(id) {
    console.log("delete Poll ID:", id);
    await deletePoll(id);
}

async function startPoll(poll) {
    try {
        const updatedPoll = await fetchApi({
            url: "polls/" + poll.id + "/start",
            method: "POST",
        });
        const index = polls.value.findIndex((p) => p.id === poll.id);
        if (index !== -1) polls.value[index] = updatedPoll;
    } catch (err) {
        alert("Erreur lors du lancement.");
    }
}

function copyLink(poll) {
    const url = window.location.origin + "/polls/" + poll.secret_token;
    navigator.clipboard.writeText(url);
    alert("Lien copié !");
}
</script>

<template>
    <p v-if="polls.length === 0" class="text-slate-500 text-center py-8">
        Aucun sondage.
    </p>

    <table v-else class="w-full border-collapse text-left">
        <thead>
            <tr class="bg-slate-100">
                <th class="border border-slate-300 px-3 py-2">Actions</th>
                <th class="border border-slate-300 px-3 py-2">ID</th>
                <th class="border border-slate-300 px-3 py-2">Titre</th>
                <th class="border border-slate-300 px-3 py-2">Question</th>
                <th class="border border-slate-300 px-3 py-2">Brouillon</th>
                <th class="border border-slate-300 px-3 py-2">Debut</th>
                <th class="border border-slate-300 px-3 py-2">Fin</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="poll in polls" :key="poll.id" class="hover:bg-slate-50">
                <td class="border border-slate-300 px-3 py-2 flex gap-2">
                    <button
                        @click="emit('edit', poll)"
                        class="bg-slate-200 text-slate-700 px-2 py-1 rounded text-sm hover:bg-slate-300"
                    >
                        Modifier
                    </button>
                    <button
                        v-if="poll.is_draft"
                        @click="startPoll(poll)"
                        class="bg-teal-600 text-white px-2 py-1 rounded text-sm hover:bg-teal-700"
                    >
                        Lancer
                    </button>
                    <button
                        v-if="!poll.is_draft"
                        @click="copyLink(poll)"
                        class="bg-teal-600 text-white px-2 py-1 rounded text-sm hover:bg-teal-700"
                    >
                        Copier le lien
                    </button>
                    <button
                        @click="delPoll(poll.id)"
                        class="bg-red-500 text-white px-2 py-1 rounded text-sm hover:bg-red-600"
                    >
                        Supp.
                    </button>
                </td>
                <td class="border border-slate-300 px-3 py-2">{{ poll.id }}</td>
                <td class="border border-slate-300 px-3 py-2">
                    {{ poll.title || "-" }}
                </td>
                <td class="border border-slate-300 px-3 py-2">
                    {{ poll.question }}
                </td>
                <td class="border border-slate-300 px-3 py-2">
                    {{ poll.is_draft ? "Oui" : "Non" }}
                </td>
                <td class="border border-slate-300 px-3 py-2">
                    {{ poll.started_at || "-" }}
                </td>
                <td class="border border-slate-300 px-3 py-2">
                    {{ poll.ends_at || "-" }}
                </td>
            </tr>
        </tbody>
    </table>
</template>
