import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
    },

    actions: {
        clearAuth() {
            this.user = null;
            localStorage.removeItem('user');
        },

        async login(credentials) {
            // O Handshake do Sanctum continua na raiz
            await axios.get('/sanctum/csrf-cookie');
            
            // O Login segue a sua arquitetura V1
            const { data } = await axios.post('/api/v1/login', credentials);
            
            this.user = data.user || data; 
            localStorage.setItem('user', JSON.stringify(this.user));
        },

        async logout() {
            try { 
                await axios.post('/api/v1/logout'); 
            } catch (e) {
                console.warn("[Security] Sessão já expirada no servidor.");
            } finally {
                this.clearAuth();
            }
        },

        async fetchUser() {
            try {
                // A validação de sessão segue a sua arquitetura V1
                const { data } = await axios.get('/api/v1/me');
                this.user = data;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (e) {
                console.error("[Security] Sessão rejeitada. Forçando expurgo local.");
                this.clearAuth();
            }
        }
    }
});