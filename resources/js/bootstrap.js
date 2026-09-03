import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

// Zero Trust: Endereço flutuante e seguro
window.axios.defaults.baseURL = import.meta.env.VITE_APP_URL || '';
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// ==========================================
// ZT-DEFENSE: Injeção de Identidade Efêmera
// ==========================================
let ephemeralToken = sessionStorage.getItem('x_ephemeral_token');
if (!ephemeralToken) {
    ephemeralToken = btoa(Math.random().toString(36).substring(2) + Date.now()).substring(0, 32);
    sessionStorage.setItem('x_ephemeral_token', ephemeralToken);
}
window.axios.defaults.headers.common['X-Ephemeral-Session'] = ephemeralToken;

// ==========================================
// INTERCEPTORS DE REQUISIÇÃO (REQUEST)
// ==========================================
window.axios.interceptors.request.use(config => {
    if (config.url && config.url.startsWith('/api/') && !config.url.startsWith('/api/v1/')) {
        config.url = config.url.replace('/api/', '/api/v1/');
    }
    
    if (config.url && config.url.includes('/cargas/motorista/minhas')) {
        config.url = config.url.replace('/cargas/motorista/minhas', '/motorista/cargas/minhas');
    }
    
    if (config.url && config.url.includes('/api/v1/cargas') && !config.url.includes('/embarcador/') && !config.url.includes('/motorista/')) {
        config.url = config.url.replace('/api/v1/cargas', '/api/v1/embarcador/cargas');
    }
    
    return config;
});

// ==========================================
// INTERCEPTORS DE RESPOSTA (RESPONSE)
// ==========================================
window.axios.interceptors.response.use(
    (response) => {
        // 1. Defesa WAF: HTML não esperado em endpoint de API
        if (typeof response.data === 'string' && response.data.includes('<!DOCTYPE html>')) {
            console.error('🔥 [WAF Interceptor] O Endpoint bloqueou o acesso.');
            return Promise.reject(new Error('Acesso negado.'));
        }

        // 2. Normalização de Envelopes de Dados (Unwrap de Paginação)
        if (response.data && typeof response.data === 'object' && response.data.data !== undefined) {
            response.data = response.data.data;
        }

        // 3. Normalização de Coleções (Tickets e Extratos)
        if (response.config.url && (response.config.url.includes('/tickets') || response.config.url.includes('/extrato'))) {
            if (!response.data) {
                response.data = [];
            } else if (!Array.isArray(response.data) && typeof response.data === 'object') {
                response.data = Object.values(response.data);
            }
        }

        // ==========================================
        // 4. BLINDAGEM ATIVA: Verifica o usuário no Login e no /me
        // ==========================================
        const userData = response.data?.user || (response.data?.id ? response.data : null);
        if (userData && userData.termo_aceite_em === null) {
            console.warn('⚖️ [LEGAL SHIELD] Usuário logado sem termos aceitos. Invocando Modal.');
            window.dispatchEvent(new CustomEvent('termo-pendente-detectado'));
        }

        return response;
    },
    (error) => {
        if (error.response) {
            const status = error.response.status;

            // 1. Defesa IAM: Sessão Expirada (Logout Forçado)
            if (status === 401 || status === 419) {
                if (window.location.pathname !== '/login') {
                    localStorage.removeItem('user'); 
                    sessionStorage.clear();
                    window.location.href = '/login';
                }
            }

            // 2. BLINDAGEM JURÍDICA: Termos de Uso Pendentes (Retorno 403 do Middleware)
            if (status === 403 && error.response.data?.error === 'TERMOS_PENDENTES') {
                console.warn('⚖️ [LEGAL SHIELD] Requisição barrada. Invocando Modal.');
                window.dispatchEvent(new CustomEvent('termo-pendente-detectado'));
            }
        }
        return Promise.reject(error);
    }
);

// ==========================================
// WEBSOCKETS (REVERB / PUSHER)
// ==========================================
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