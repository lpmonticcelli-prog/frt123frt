<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const email = ref('');
const token = ref('');
const password = ref('');
const password_confirmation = ref('');
const errorMsg = ref('');
const successMsg = ref('');
const loading = ref(false);

// Assim que a tela carrega, capturamos os dados da URL (enviados pelo link do e-mail)
onMounted(() => {
    email.value = route.query.email || '';
    token.value = route.query.token || '';

    if (!token.value) {
        errorMsg.value = 'Token de segurança ausente. Por favor, clique novamente no link enviado para o seu e-mail.';
    }
});

const handleReset = async () => {
    // Validação básica de UX
    if (password.value !== password_confirmation.value) {
        errorMsg.value = 'As senhas não coincidem.';
        return;
    }

    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    try {
        const response = await axios.post('/api/reset-password', {
            token: token.value,
            email: email.value,
            password: password.value,
            password_confirmation: password_confirmation.value
        });

        successMsg.value = response.data.message || 'Senha redefinida com sucesso!';
        
        // Redireciona para o login após 3 segundos
        setTimeout(() => {
            router.push('/login');
        }, 3000);

    } catch (error) {
        if (error.response?.data?.errors) {
            errorMsg.value = Object.values(error.response.data.errors)[0][0];
        } else {
            errorMsg.value = error.response?.data?.message || 'Falha ao redefinir senha. O link pode ter expirado.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-950 p-6">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-10 border border-gray-100">
      
      <div class="text-center mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">123<span class="text-orange-600">fretei</span></h1>
        <p class="text-slate-500 text-sm mt-2">Crie sua nova senha</p>
      </div>
      
      <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 text-red-700 text-xs font-bold rounded-xl border border-red-100 flex items-start">
        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
        <span>{{ errorMsg }}</span>
      </div>

      <div v-if="successMsg" class="mb-6 p-4 bg-green-50 text-green-800 text-xs font-bold rounded-xl border border-green-200 flex items-start">
        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span>{{ successMsg }} Redirecionando...</span>
      </div>

      <form v-if="!successMsg" @submit.prevent="handleReset" class="space-y-5">
        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 ml-1">E-mail (Bloqueado)</label>
          <input 
            type="email" 
            :value="email" 
            disabled 
            class="w-full bg-slate-100 border-slate-200 text-slate-500 rounded-xl p-3 text-sm cursor-not-allowed" 
          />
        </div>

        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 ml-1">Nova Senha</label>
          <input 
            type="password" 
            v-model="password" 
            placeholder="Mínimo 8 caracteres" 
            required 
            minlength="8"
            class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-slate-900 focus:bg-white outline-none transition-all" 
          />
        </div>

        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 ml-1">Confirmar Nova Senha</label>
          <input 
            type="password" 
            v-model="password_confirmation" 
            placeholder="Repita a senha" 
            required 
            class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-slate-900 focus:bg-white outline-none transition-all" 
          />
        </div>
        
        <button 
            type="submit" 
            :disabled="loading || !token" 
            class="w-full bg-orange-600 hover:bg-orange-700 text-white p-3 rounded-xl font-bold text-sm shadow-lg shadow-orange-200 disabled:opacity-50 transition-all active:scale-[0.98]"
        >
          {{ loading ? 'Salvando...' : 'Redefinir Senha' }}
        </button>
      </form>

    </div>
  </div>
</template>