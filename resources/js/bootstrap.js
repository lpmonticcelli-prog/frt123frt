import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Configuração Global do Axios (Motor de Requisições)
 * --------------------------------------------------------------------------
 */
window.axios = axios;

// [CRÍTICO] Aponta estritamente para o container Docker isolado na porta 8000 (Cloud Native)
window.axios.defaults.baseURL = 'http://localhost:8000';

// Identifica para o Laravel que esta é uma requisição AJAX (XHR)
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// BLINDAGEM SPA: Força a negociação de conteúdo para JSON estrito.
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';

/**
 * Autenticação SPA Segura (Laravel Sanctum / Cookies Stateful)
 */
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

/**
 * Interceptadores de Resposta (Escalabilidade e Tratamento Global)
 */
window.axios.interceptors.response.use(
    (response) => {
        // Interceptador de Segurança: Aborta requisições que devolvam HTML no lugar de JSON
        if (typeof response.data === 'string' && response.data.includes('<!DOCTYPE html>')) {
            console.error('🔥 [Axios Interceptor] API retornou HTML em vez de JSON. Rota inexistente no backend.');
            return Promise.reject(new Error('Endpoint não encontrado no backend.'));
        }
        return response; // Sucesso (2xx) segue o fluxo normal
    },
    (error) => {
        if (error.response) {
            const status = error.response.status;

            // Tratamento Crítico: 401 (Sessão Inválida) ou 419 (CSRF Token Expirado)
            if (status === 401 || status === 419) {
                console.error(`[Axios Interceptor] Quebra de Segurança (${status}). Sessão ejetada.`);

                // Previne Loop Infinito de redirecionamento se o próprio login der erro
                if (window.location.pathname !== '/login') {
                    // Expurgo de Estado Fantasma
                    localStorage.removeItem('user'); 
                    localStorage.removeItem('auth');
                    sessionStorage.clear();
                    window.location.href = '/login';
                }
            }
            
            // Tratamento de Rate Limit: 429 (Too Many Requests / Anti-DDoS)
            if (status === 429) {
                console.warn('[Axios Interceptor] Proteção Anti-DDoS acionada (Rate Limit excedido).');
            }
        }
        return Promise.reject(error);
    }
);

window.axios.interceptors.request.use(config => { console.log('🚀 [AXIOS DISPARO]', config.baseURL || '', config.url); return config; });

// [INJEÇÃO CLÍNICA] Corretor Automático de Versionamento (V1)
window.axios.interceptors.request.use(config => {
    if (config.url && config.url.startsWith('/api/') && !config.url.startsWith('/api/v1/')) {
        config.url = config.url.replace('/api/', '/api/v1/');
    }
    return config;
});

// [INJEÇÃO CLÍNICA] Normalizador de Payload (Resolve o erro .reduce())
window.axios.interceptors.response.use(response => {
    if (response.data && Array.isArray(response.data.data) && typeof response.data.total === 'number') {
        response.data = response.data.data; // Desempacota a paginação automaticamente
    }
    return response;
}, error => Promise.reject(error));

// [INJEÇÃO CLÍNICA] Coerção de Tipos e Blindagem Anti-Crash
window.axios.interceptors.response.use(response => {
    // 1. Extração Agressiva: Desempacota "data" de API Resources ou Paginações
    if (response.data && typeof response.data === 'object' && response.data.data !== undefined) {
        response.data = response.data.data;
    }

    // 2. Blindagem de Tipos (Resolve os erros .length e .reduce)
    if (response.config.url && (response.config.url.includes('/tickets') || response.config.url.includes('/extrato'))) {
        if (!response.data) {
            response.data = [];
        } 
        else if (!Array.isArray(response.data) && typeof response.data === 'object') {
            response.data = Object.values(response.data);
        }
    }
    return response;
}, error => Promise.reject(error));

// [INJEÇÃO CLÍNICA] Auto-Correção de Pathing do Motorista
window.axios.interceptors.request.use(config => {
    if (config.url && config.url.includes('/cargas/motorista/minhas')) {
        config.url = config.url.replace('/cargas/motorista/minhas', '/motorista/cargas/minhas');
    }
    return config;
});

// [INJEÇÃO CLÍNICA] Auto-Correção de Pathing do Embarcador
window.axios.interceptors.request.use(config => {
    if (config.url && config.url.includes('/api/v1/cargas') && !config.url.includes('/embarcador/') && !config.url.includes('/motorista/')) {
        config.url = config.url.replace('/api/v1/cargas', '/api/v1/embarcador/cargas');
    }
    return config;
});

/**
 * ==========================================
 * MALHA DE EVENTOS REAL-TIME (LARAVEL REVERB)
 * ==========================================
 * Transforma a SPA num cliente reativo capaz de escutar PostgreSQL via Redis/Reverb
 */
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8082,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8082,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});