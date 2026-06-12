import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        // ZT-DEFENSE: Extinção de LocalStorage (CWE-312).
        // A sessão agora existe puramente na RAM do navegador, ancorada pelo Cookie HttpOnly.
        // Malwares que varrem o disco rígido buscando Json/LocalStorage não acharão credenciais.
        user: null, 
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
    },

    actions: {
        clearAuth() {
            this.user = null;
            // Purga vestígios legados de ataques passados
            localStorage.removeItem('user');
            localStorage.removeItem('auth');
            sessionStorage.clear();
        },

        async login(credentials) {
            await axios.get('/sanctum/csrf-cookie');
            const { data } = await axios.post('/api/v1/login', credentials);
            
            this.user = data.user || data; 
        },

        async logout() {
            try { 
                await axios.post('/api/v1/logout'); 
            } catch (e) {
                console.warn("[Security] Sessão limpa ou ejetada pelo WAF.");
            } finally {
                this.clearAuth();
            }
        },

        async fetchUser() {
            try {
                const { data } = await axios.get('/api/v1/me');
                this.user = data;
            } catch (e) {
                console.error("[Security] Cookie rejeitado por anomalia de contexto. Expurgando.");
                this.clearAuth();
            }
        }
    }
});