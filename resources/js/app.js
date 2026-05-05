import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import { vMaska } from "maska/vue";
import App from './App.vue';

const app = createApp(App);
const pinia = createPinia();

// 1. Injeção de Estado e Roteamento
app.use(pinia);
app.use(router);

// 2. Diretivas Globais
app.directive("maska", vMaska);

// ==========================================
// 3. ESCUDO GLOBAL DE ERROS (Anti-Tela Branca)
// ==========================================
app.config.errorHandler = (err, instance, info) => {
    // Intercepta qualquer falha no Vue (como rotas inexistentes ou variáveis nulas)
    console.error('🔥 [Vue Global Crash Prevented]:', err.message || err);
    
    // Se o erro for de roteamento (ex: No match found), não deixa a tela morrer
    if (err.message && err.message.includes('No match for')) {
        console.warn('⚠️ Rota não encontrada. Redirecionamento abortado pelo escudo de segurança.');
        // Opcional: router.push('/admin/dashboard');
    }
};

// Captura erros de Promises não tratadas no front-end (ex: falhas no Axios)
window.addEventListener('unhandledrejection', function(event) {
    console.warn('🌐 [Async Error Prevented]:', event.reason);
});

// 4. Montagem da Aplicação
app.mount('#app');import "./route-fixer.js";
