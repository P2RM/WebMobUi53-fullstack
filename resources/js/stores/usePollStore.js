import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

const polls = ref([]); //singleton (pr tous composants)

//chaque appel return mm polls
export function usePollStore() {
  const { fetchApi } = useFetchApi();

  //polls = sondages recu
  function setPolls(data) {
    polls.value = data;
  }

  //supp poll du tableau (seul truc partagé par composants diff)
  async function deletePoll(id) {
    const result = await fetchApi({ url: 'polls/' + id, method: 'DELETE' });
    if (result) {
      polls.value = polls.value.filter(p => p.id !== id);
    }
  }

  //return uniquement nécessaire -> fetchApi reste pv
  return { polls, setPolls, deletePoll };
}
