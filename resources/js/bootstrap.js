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
// Gera uma assinatura volátil que existe apenas enquanto a aba estiver aberta.
// Se um malware roubar o cookie e mandar para a Rússia via cURL, a requisição não 
// possuirá esse Header volátil. A defesa passiva será ativada instantaneamente.
let ephemeralToken = sessionStorage.getItem('x_ephemeral_token');
if (!ephemeralToken) {
    ephemeralToken = btoa(Math.random().toString(36).substring(2) + Date.now()).substring(0, 32);
    sessionStorage.setItem('x_ephemeral_token', ephemeralToken);
}
window.axios.defaults.headers.common['X-Ephemeral-Session'] = ephemeralToken;

window.axios.interceptors.response.use(
    (response) => {
        if (typeof response.data === 'string' && response.data.includes('<!DOCTYPE html>')) {
            console.error('🔥 [WAF Interceptor] O Endpoint bloqueou o acesso.');
            return Promise.reject(new Error('Acesso negado.'));
        }
        return response;
    },
    (error) => {
        if (error.response) {
            const status = error.response.status;

            if (status === 401 || status === 419) {
                if (window.location.pathname !== '/login') {
                    localStorage.removeItem('user'); 
                    sessionStorage.clear();
                    window.location.href = '/login';
                }
            }
        }
        return Promise.reject(error);
    }
);

window.axios.interceptors.request.use(config => {
    if (config.url && config.url.startsWith('/api/') && !config.url.startsWith('/api/v1/')) {
        config.url = config.url.replace('/api/', '/api/v1/');
    }
    return config;
});

window.axios.interceptors.response.use(response => {
    if (response.data && Array.isArray(response.data.data) && typeof response.data.total === 'number') {
        response.data = response.data.data;
    }
    return response;
}, error => Promise.reject(error));

window.axios.interceptors.response.use(response => {
    if (response.data && typeof response.data === 'object' && response.data.data !== undefined) {
        response.data = response.data.data;
    }
    if (response.config.url && (response.config.url.includes('/tickets') || response.config.url.includes('/extrato'))) {
        if (!response.data) {
            response.data = [];
        } else if (!Array.isArray(response.data) && typeof response.data === 'object') {
            response.data = Object.values(response.data);
        }
    }
    return response;
}, error => Promise.reject(error));

window.axios.interceptors.request.use(config => {
    if (config.url && config.url.includes('/cargas/motorista/minhas')) {
        config.url = config.url.replace('/cargas/motorista/minhas', '/motorista/cargas/minhas');
    }
    return config;
});

window.axios.interceptors.request.use(config => {
    if (config.url && config.url.includes('/api/v1/cargas') && !config.url.includes('/embarcador/') && !config.url.includes('/motorista/')) {
        config.url = config.url.replace('/api/v1/cargas', '/api/v1/embarcador/cargas');
    }
    return config;
});

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