import { defineStore } from 'pinia';
import axios from 'axios'; // As configurações de segurança já vêm do bootstrap.js

export const useAuthStore = defineStore('auth', {
    state: () => ({
        // O token foi eliminado. Dependemos apenas da existência do objeto do usuário.
        // A segurança real (a chave do cofre) agora vive no Cookie HttpOnly do navegador.
        user: JSON.parse(localStorage.getItem('user')) || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
    },

    actions: {
        // --- FUNÇÃO AUXILIAR DE ENCAPSULAMENTO ---
        
        clearAuth() {
            this.user = null;
            localStorage.removeItem('user');
            // Não há mais tokens Bearer nem headers para deletar.
        },

        // --- FLUXO PRINCIPAL ---

        async login(credentials) {
            // PASSO 1 CRÍTICO: Handshake CSRF.
            // Pede ao Laravel para inicializar uma sessão e enviar os cookies XSRF-TOKEN e laravel_session.
            await axios.get('/sanctum/csrf-cookie');
            
            // PASSO 2: Autenticação.
            // O Axios (via bootstrap.js) anexa o cookie CSRF automaticamente a este POST.
            const { data } = await axios.post('/api/login', credentials);
            
            // O backend ainda pode retornar um access_token na resposta (se você usa API híbrida para mobile),
            // mas no Vue ignoramos isso. Salvamos apenas os dados do usuário.
            this.user = data.user || data; 
            localStorage.setItem('user', JSON.stringify(this.user));
        },

        async logout() {
            try { 
                // O cookie de sessão viaja automaticamente no header, autorizando a invalidação no backend.
                await axios.post('/api/logout'); 
            } catch (e) {
                console.warn("Sessão já expirada ou inválida no servidor.");
            } finally {
                // Limpeza do estado local garantida.
                this.clearAuth();
            }
        },

        /**
         * Sincroniza os dados do banco com o estado do Vue.
         */
        async fetchUser() {
            try {
                // Não é mais necessário reinjetar token no caso de F5. 
                // O navegador envia o Cookie HttpOnly automaticamente.
                const { data } = await axios.get('/api/me');
                
                this.user = data;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (e) {
                console.error("Sessão rejeitada pelo servidor. Forçando logout local...");
                this.clearAuth();
            }
        }
    }
});