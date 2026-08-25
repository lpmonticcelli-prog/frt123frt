<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import axios from 'axios'; 

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

// Estados
const isRecovering = ref(false); 
const email = ref('');
const password = ref('');
const showPassword = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const loading = ref(false);

const toggleView = () => {
    isRecovering.value = !isRecovering.value;
    errorMsg.value = '';
    successMsg.value = '';
    password.value = '';
};

// =======================================================================
// INTERCEPTADOR DO RETORNO DO GOOGLE (À Prova de Falhas - JS Nativo)
// =======================================================================
onMounted(async () => {
    // Lê a URL bruta diretamente do navegador instantaneamente
    const urlParams = new URLSearchParams(window.location.search);
    const tokenDaUrl = urlParams.get('token');
    const erroDaUrl = urlParams.get('error');

    // Verifica se voltou do Google com erro
    if (erroDaUrl === 'google_falhou') {
        errorMsg.value = 'Falha ao tentar se conectar com o Google. Tente novamente.';
        // Limpa a URL na marra sem recarregar a página
        window.history.replaceState({}, document.title, window.location.pathname);
        return;
    }

    // Verifica se voltou do Google com sucesso (com token na URL)
    if (tokenDaUrl) {
        loading.value = true;
        
        try {
            // 1. Salva o token no localStorage
            localStorage.setItem('auth_token', tokenDaUrl);
            
            // 2. Configura o Axios para usar esse token imediatamente
            axios.defaults.headers.common['Authorization'] = `Bearer ${tokenDaUrl}`;
            
            // 3. Limpa a URL instantaneamente para não deixar o token visível
            window.history.replaceState({}, document.title, window.location.pathname);

            // 4. Pede os dados do usuário para o backend
            const response = await axios.get('/api/me');
            
            // 5. Atualiza a Store do Pinia
            authStore.user = response.data;
            
            // =========================================================
            // 6. ROTEAMENTO BLINDADO (LISTA BRANCA)
            // =========================================================
            const role = authStore.user?.role?.slug || '';
            const safeRole = String(role).trim().toLowerCase();
            const staffRoles = ['admin', 'manager', 'compliance', 'suporte_n1'];

            // 1. Se for EXATAMENTE motorista ou embarcador, vai pro painel
            if (safeRole === 'motorista' || safeRole === 'embarcador') {
                router.push(`/${safeRole}/painel`); 
            } 
            // 2. Se for da equipe administrativa
            else if (staffRoles.includes(safeRole)) {
                if (safeRole === 'suporte_n1') {
                    router.push('/admin/suporte'); 
                } else {
                    router.push('/admin/dashboard'); 
                }
            } 
            // 3. Qualquer outra coisa (nulo, undefined, vazio) joga pra tela de escolha
            else {
                router.push({ name: 'ChooseProfile' }); 
            }
            
        } catch (error) {
            console.error('Erro ao processar token do Google:', error);
            errorMsg.value = 'A sessão do Google expirou ou é inválida. Faça login novamente.';
            localStorage.removeItem('auth_token');
            delete axios.defaults.headers.common['Authorization'];
        } finally {
            loading.value = false;
        }
    }
});

// =======================================================================
// LOGIN TRADICIONAL (E-MAIL E SENHA)
// =======================================================================
const handleLogin = async () => {
    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    
    try {
        await authStore.login({ 
            email: email.value, 
            password: password.value 
        });
        
        // =========================================================
        // ROTEAMENTO BLINDADO (LISTA BRANCA)
        // =========================================================
        const role = authStore.user?.role?.slug || '';
        const safeRole = String(role).trim().toLowerCase();
        const staffRoles = ['admin', 'manager', 'compliance', 'suporte_n1'];

        // 1. Se for EXATAMENTE motorista ou embarcador, vai pro painel
        if (safeRole === 'motorista' || safeRole === 'embarcador') {
            router.push(`/${safeRole}/painel`); 
        } 
        // 2. Se for da equipe administrativa
        else if (staffRoles.includes(safeRole)) {
            if (safeRole === 'suporte_n1') {
                router.push('/admin/suporte'); 
            } else {
                router.push('/admin/dashboard'); 
            }
        } 
        // 3. Qualquer outra coisa (nulo, undefined, vazio) joga pra tela de escolha
        else {
            router.push({ name: 'ChooseProfile' }); 
        }
        
    } catch (error) {
        password.value = ''; 

        if (error.response?.data?.errors) {
            errorMsg.value = Object.values(error.response.data.errors)[0][0];
        } else {
            errorMsg.value = error.response?.data?.message || 'Falha na conexão com o servidor.';
        }
    } finally {
        loading.value = false;
    }
};

const handleRecovery = async () => {
    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    try {
        await axios.post('/api/forgot-password', { email: email.value });
        successMsg.value = 'Instruções de redefinição foram enviadas para o seu e-mail.';
        email.value = ''; 
    } catch (error) {
        if (error.response?.data?.errors) {
            errorMsg.value = Object.values(error.response.data.errors)[0][0];
        } else {
            errorMsg.value = error.response?.data?.message || 'Erro ao solicitar redefinição. Tente novamente mais tarde.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-surface-950 p-6 selection:bg-brand-500 selection:text-white">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-10 border border-surface-200 transition-all duration-300">
      
      <div class="text-center mb-8">
        <div class="flex items-center justify-center gap-2 mb-2">
          <img src="/logo1.png" alt="Logotipo 123fretei" class="h-8 w-auto object-contain shrink-0" />
          <span class="text-3xl font-extrabold tracking-tight flex items-baseline">
            <span class="text-[#035D29]">123</span><span class="text-brand-600">fretei</span>
          </span>
        </div>
        <p class="text-surface-500 text-sm mt-2">
            {{ isRecovering ? 'Recuperação de acesso' : 'Acesse o seu painel operacional' }}
        </p>
      </div>
      
      <div v-if="errorMsg" class="mb-6 p-4 bg-rose-50 text-rose-700 text-xs font-bold rounded-xl border border-rose-100 flex items-start">
        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span>{{ errorMsg }}</span>
      </div>

      <div v-if="successMsg" class="mb-6 p-4 bg-emerald-50 text-emerald-800 text-xs font-bold rounded-xl border border-emerald-200 flex items-start">
        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>{{ successMsg }}</span>
      </div>

      <!-- BOTÃO DO GOOGLE NO LOGIN -->
      <div v-if="!isRecovering" class="mb-6">
        <a href="/api/auth/google/redirect" class="w-full flex items-center justify-center px-4 py-2.5 border border-surface-300 shadow-sm text-sm font-semibold rounded-xl text-surface-700 bg-white hover:bg-surface-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
            </svg>
            Entrar com o Google
        </a>
      </div>

      <div v-if="!isRecovering" class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-surface-200"></div>
        </div>
        <div class="relative flex justify-center text-[10px] uppercase font-bold tracking-widest text-surface-400">
            <span class="px-3 bg-white">Ou use seu e-mail</span>
        </div>
      </div>

      <form v-if="!isRecovering" @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-1 ml-1">E-mail</label>
          <input 
            type="email" 
            v-model.trim="email" 
            autocomplete="email"
            placeholder="seu@email.com" 
            required 
            class="w-full bg-white border border-surface-300 rounded-xl p-3 text-sm text-surface-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500 outline-none transition-all placeholder:text-surface-400" 
          />
        </div>

        <div>
          <div class="flex justify-between items-center mb-1 ml-1 pr-1">
            <label class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest">Senha</label>
            <button type="button" @click="toggleView" class="text-[11px] font-bold text-brand-600 hover:text-brand-700 transition-colors focus:outline-none">
              Esqueceu?
            </button>
          </div>
          <div class="relative">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              v-model="password" 
              autocomplete="current-password"
              placeholder="••••••••" 
              required 
              class="w-full bg-white border border-surface-300 rounded-xl p-3 pr-10 text-sm text-surface-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500 outline-none transition-all placeholder:text-surface-400" 
            />
            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-surface-400 hover:text-brand-600 focus:outline-none">
              <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
            </button>
          </div>
        </div>
        
        <button 
            type="submit" 
            :disabled="loading" 
            class="w-full bg-brand-500 hover:bg-brand-600 text-white p-3 rounded-xl font-bold text-sm shadow-lg shadow-brand-200/50 disabled:opacity-50 transition-all active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 mt-2"
        >
          {{ loading ? 'Autenticando...' : 'Entrar' }}
        </button>

        <div class="pt-4 text-center">
            <router-link to="/register/motorista" class="text-xs text-surface-500 hover:text-brand-600 font-medium transition-colors block mb-2 focus:outline-none">
                É motorista? Cadastre-se aqui
            </router-link>
            <router-link to="/register/embarcador" class="text-xs text-surface-500 hover:text-brand-600 font-medium transition-colors block focus:outline-none">
                Embarcador ou Operador logístico? Acesse o portal
            </router-link>
        </div>
      </form>

      <form v-else @submit.prevent="handleRecovery" class="space-y-5">
        <p class="text-xs text-surface-500 text-center mb-4">Informe o seu e-mail cadastrado. Enviaremos um link seguro para a redefinição da sua senha.</p>
        
        <div>
          <label class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-1 ml-1">E-mail</label>
          <input 
            type="email" 
            v-model.trim="email" 
            autocomplete="email"
            placeholder="seu@email.com" 
            required 
            class="w-full bg-white border border-surface-300 rounded-xl p-3 text-sm text-surface-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500 outline-none transition-all placeholder:text-surface-400" 
          />
        </div>

        <button 
            type="submit" 
            :disabled="loading" 
            class="w-full bg-surface-900 hover:bg-surface-800 text-white p-3 rounded-xl font-bold text-sm shadow-lg shadow-surface-200 disabled:opacity-50 transition-all active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-surface-900 focus:ring-offset-2 mt-2"
        >
          {{ loading ? 'Enviando...' : 'Enviar Link de Recuperação' }}
        </button>

        <div class="pt-2 text-center">
            <button type="button" @click="toggleView" class="text-xs text-surface-500 hover:text-brand-600 font-medium transition-colors focus:outline-none">
                Voltar para o login
            </button>
        </div>
      </form>

    </div>
  </div>
</template>