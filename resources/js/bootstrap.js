import axios from 'axios';

/**
 * Configuração Global do Axios (Motor de Requisições)
 * --------------------------------------------------------------------------
 */
window.axios = axios;

// Identifica para o Laravel que esta é uma requisição AJAX (XHR)
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// BLINDAGEM SPA: Força a negociação de conteúdo para JSON estrito.
// Isso impede que o Laravel devolva uma página HTML de erro ou redirecionamento (302)
// caso a sessão expire, o que quebraria o front-end silenciosamente.
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';

/**
 * Autenticação SPA Segura (Laravel Sanctum / Cookies Stateful)
 * --------------------------------------------------------------------------
 * Substitui o antigo uso de Tokens manuais (Bearer).
 * Obriga o Axios a enviar automaticamente o cookie de sessão e o token CSRF.
 */
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

/**
 * Interceptadores de Resposta (Escalabilidade e Tratamento Global)
 * --------------------------------------------------------------------------
 */
window.axios.interceptors.response.use(
    (response) => response, // Sucesso (2xx) segue o fluxo normal
    (error) => {
        if (error.response) {
            const status = error.response.status;

            // Tratamento Crítico: 401 (Sessão Inválida) ou 419 (CSRF Token Expirado)
            if (status === 401 || status === 419) {
                console.error(`[Axios Interceptor] Quebra de Segurança (${status}). Sessão ejetada.`);

                // Previne Loop Infinito de redirecionamento se o próprio login der erro
                if (window.location.pathname !== '/login') {
                    
                    // Expurgo de Estado Fantasma
                    // Limpa o estado local do Pinia (auth) para não haver resíduos
                    localStorage.removeItem('auth'); 
                    sessionStorage.clear();

                    // Hard Redirect: Força o navegador a limpar a memória e pedir 
                    // um novo ciclo de CSRF Handshake ao Laravel
                    window.location.href = '/login';
                }
            }
            
            // Tratamento de Rate Limit: 429 (Too Many Requests / Anti-DDoS)
            if (status === 429) {
                console.warn('[Axios Interceptor] Proteção Anti-DDoS acionada (Rate Limit excedido).');
            }
        }

        // Repassa o erro para que os componentes (ex: views) possam tratá-lo
        return Promise.reject(error);
    }
);

/**
 * Echo (Real-time WebSockets) - Preparação para o Futuro
 * --------------------------------------------------------------------------
 * Deixado preparado para quando for ativar o acompanhamento de fretes em tempo real.
 */
// import Echo from 'laravel-echo';
// import pusher from 'pusher-js';
// window.Pusher = pusher;
// window.Echo = new Echo({ ... });