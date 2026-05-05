<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// Estado visual do formulário (com as máscaras no ecrã)
const form = ref({
    name: '',
    email: '',
    password: '',
    phone: '',
    razao_social: '',
    cnpj: '',
    inscricao_estadual: ''
});

// Estado limpo (apenas números) para enviar ao backend de forma higienizada
const formUnmasked = ref({
    phone: '',
    cnpj: ''
});

const loading = ref(false);
const errorMsg = ref('');

const handleRegister = async () => {
    loading.value = true;
    errorMsg.value = '';
    
    // Montamos o payload trocando as versões mascaradas pelas versões limpas
    const payload = {
        ...form.value,
        phone: formUnmasked.value.phone,
        cnpj: formUnmasked.value.cnpj
    };

    try {
        // 1. Assegura a proteção CSRF do Laravel Sanctum (Essencial para SPA)
        await axios.get('/sanctum/csrf-cookie');
        
        // 2. Envia os dados (O backend fará a validação na Receita Federal e injetará o Cookie)
        const { data } = await axios.post('/api/register/embarcador', payload);
        
        // 3. Atualiza o estado global da aplicação
        authStore.user = data.user;
        
        // 4. Redireciona de forma segura pelo nome da rota
        router.push({ name: 'EmbarcadorDashboard' });
    } catch (error) {
        if (error.response?.data?.errors) {
            // Captura o erro específico (ex: CNPJ Inválido da ReceitaWS) e exibe ao utilizador
            const firstError = Object.values(error.response.data.errors)[0][0];
            errorMsg.value = firstError;
        } else {
            errorMsg.value = error.response?.data?.message || 'Erro crítico ao processar o registo.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#F5F5F7] p-6 font-sans">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-sm border border-gray-200 p-10">
      
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-gray-900">Registo de Indústria</h2>
        <p class="text-gray-500 mt-2">Preencha os dados corporativos para iniciar a homologação.</p>
      </div>

      <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium animate-pulse">
        {{ errorMsg }}
      </div>

      <form @submit.prevent="handleRegister" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-2">Dados de Acesso</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Responsável</label>
                    <input type="text" v-model="form.name" required class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail Corporativo</label>
                    <input type="email" v-model="form.email" required class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha Segura</label>
                    <input type="password" v-model="form.password" required minlength="8" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone / WhatsApp</label>
                    <input 
                        type="text" 
                        v-model="form.phone" 
                        v-maska 
                        data-maska="(##) #####-####" 
                        @maska="formUnmasked.phone = $event.detail.unmasked"
                        placeholder="(11) 99999-9999"
                        required 
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors"
                    >
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-2">Dados da Empresa</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razão Social</label>
                    <input type="text" v-model="form.razao_social" required class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                    <input 
                        type="text" 
                        v-model="form.cnpj" 
                        v-maska 
                        data-maska="##.###.###/####-##" 
                        @maska="formUnmasked.cnpj = $event.detail.unmasked"
                        placeholder="00.000.000/0000-00"
                        required 
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Estadual</label>
                    <input type="text" v-model="form.inscricao_estadual" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
                    <p class="text-xs text-gray-400 mt-1">Deixe em branco se for isento.</p>
                </div>
            </div>
        </div>

        <div class="pt-4 flex items-center justify-between border-t border-gray-100">
            <router-link to="/login" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                ← Voltar ao Login
            </router-link>
            
            <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-8 rounded-lg shadow-md disabled:opacity-50 transition-all">
                {{ loading ? 'A Analisar Dados (RFB)...' : 'Solicitar Homologação' }}
            </button>
        </div>
      </form>
    </div>
  </div>
</template>