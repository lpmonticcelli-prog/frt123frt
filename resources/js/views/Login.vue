<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import axios from 'axios'; 

const router = useRouter();
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

const handleLogin = async () => {
    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    
    try {
        await authStore.login({ 
            email: email.value, 
            password: password.value 
        });
        
        const role = authStore.user?.role?.slug;
        const staffRoles = ['admin', 'manager', 'compliance', 'suporte_n1'];

        // Redirecionamento Inteligente Pós-Login
        if (staffRoles.includes(role)) {
            if (role === 'suporte_n1') {
                router.push('/admin/suporte'); // Direto para a Mesa de Operações
            } else {
                router.push('/admin/dashboard'); // Admin/Manager vão pro painel geral
            }
        } else if (role) {
            router.push(`/${role}/painel`); // Embarcador ou Motorista
        } else {
            router.push('/'); 
        }
        
    } catch (error) {
        // Limpeza por segurança: força o usuário a digitar novamente
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
  <div class="min-h-screen flex items-center justify-center bg-slate-950 p-6">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-10 border border-gray-100 transition-all duration-300">
      
      <div class="text-center mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">123<span class="text-blue-600">fretei</span></h1>
        <p class="text-slate-500 text-sm mt-2">
            {{ isRecovering ? 'Recuperação de acesso' : 'Acesse o seu painel operacional' }}
        </p>
      </div>
      
      <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 text-red-700 text-xs font-bold rounded-xl border border-red-100 flex items-start">
        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span>{{ errorMsg }}</span>
      </div>

      <div v-if="successMsg" class="mb-6 p-4 bg-green-50 text-green-800 text-xs font-bold rounded-xl border border-green-200 flex items-start">
        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>{{ successMsg }}</span>
      </div>

      <form v-if="!isRecovering" @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 ml-1">E-mail</label>
          <input 
            type="email" 
            v-model.trim="email" 
            placeholder="seu@email.com" 
            required 
            class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-slate-900 focus:bg-white outline-none transition-all" 
          />
        </div>

        <div>
          <div class="flex justify-between items-center mb-1 ml-1 pr-1">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Senha</label>
            <button type="button" @click="toggleView" class="text-[11px] font-bold text-blue-600 hover:text-blue-700 transition-colors">
              Esqueceu?
            </button>
          </div>
          <div class="relative">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              v-model="password" 
              placeholder="••••••••" 
              required 
              class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 pr-10 text-sm focus:ring-2 focus:ring-slate-900 focus:bg-white outline-none transition-all" 
            />
            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
              <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
            </button>
          </div>
        </div>
        
        <button 
            type="submit" 
            :disabled="loading" 
            class="w-full bg-slate-900 hover:bg-black text-white p-3 rounded-xl font-bold text-sm shadow-lg shadow-slate-200 disabled:opacity-50 transition-all active:scale-[0.98]"
        >
          {{ loading ? 'Autenticando...' : 'Entrar' }}
        </button>

        <div class="pt-4 text-center">
            <router-link to="/register/motorista" class="text-xs text-slate-500 hover:text-slate-800 font-medium transition-colors block mb-2">
                É motorista? Cadastre-se aqui
            </router-link>
            <router-link to="/register/embarcador" class="text-xs text-slate-500 hover:text-slate-800 font-medium transition-colors block">
                Embarcador ou Operador logístico? Acesse o portal
            </router-link>
        </div>
      </form>

      <form v-else @submit.prevent="handleRecovery" class="space-y-5">
        <p class="text-xs text-slate-500 text-center mb-4">Informe o seu e-mail cadastrado. Enviaremos um link seguro para a redefinição da sua senha.</p>
        
        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 ml-1">E-mail</label>
          <input 
            type="email" 
            v-model.trim="email" 
            placeholder="seu@email.com" 
            required 
            class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-slate-900 focus:bg-white outline-none transition-all" 
          />
        </div>

        <button 
            type="submit" 
            :disabled="loading" 
            class="w-full bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-xl font-bold text-sm shadow-lg shadow-blue-200 disabled:opacity-50 transition-all active:scale-[0.98]"
        >
          {{ loading ? 'Enviando...' : 'Enviar Link de Recuperação' }}
        </button>

        <div class="pt-2 text-center">
            <button type="button" @click="toggleView" class="text-xs text-slate-500 hover:text-slate-800 font-medium transition-colors">
                Voltar para o login
            </button>
        </div>
      </form>

    </div>
  </div>
</template>