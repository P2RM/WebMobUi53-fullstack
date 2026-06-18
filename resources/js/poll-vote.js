// lien entre blade/html et composant vue
import './bootstrap';
import { createApp } from 'vue';
import App from './AppPollVote.vue';

const el = document.getElementById('app'); //recup de l'el du blade
const props = JSON.parse(el.dataset.props ?? '{}'); //parse l'html de l'el

createApp(App, props).mount(el); //monte le vue dans l'el du blade
