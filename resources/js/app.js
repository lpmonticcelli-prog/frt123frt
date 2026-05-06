import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import { vMaska } from "maska/vue";
import App from './App.vue';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.directive("maska", vMaska);

app.config.errorHandler = (err, instance, info) => {
    console.error('🔥 [Vue Global Crash Prevented]:', err.message || err);
    if (err.message && err.message.includes('No match for')) {
        console.warn('⚠️ Rota não encontrada. Redirecionamento abortado pelo escudo de segurança.');
    }
};

window.addEventListener('unhandledrejection', function(event) {
    console.warn('🌐 [Async Error Prevented]:', event.reason);
});

app.mount('#app');