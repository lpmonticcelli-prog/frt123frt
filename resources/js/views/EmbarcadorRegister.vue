<template>
  <div class="min-h-screen flex items-center justify-center bg-[#F5F5F7] p-6 font-sans">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-sm border border-gray-200 p-10 relative">
      
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-gray-900">
            {{ isGoogleUser ? 'Complete seu Perfil' : 'Registro de Indústria' }}
        </h2>
        <p class="text-gray-500 mt-2">
            {{ isGoogleUser ? 'Forneça o CNPJ para finalizarmos sua homologação.' : 'Preencha os dados corporativos para iniciar a homologação.' }}
        </p>
      </div>

      <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium animate-pulse">
        {{ errorMsg }}
      </div>

      <!-- BOTÃO DO GOOGLE -->
      <div v-if="!isGoogleUser" class="mb-6">
        <a href="/api/auth/google/redirect" class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
            </svg>
            Cadastrar com o Google
        </a>
      </div>

      <div v-if="!isGoogleUser" class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-gray-500 font-medium">Ou cadastre com e-mail</span>
        </div>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-2">Dados de Acesso</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Responsável</label>
                    <input type="text" v-model="form.name" required :disabled="isGoogleUser" :class="{'bg-gray-100 cursor-not-allowed text-gray-500': isGoogleUser}" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail Corporativo</label>
                    <input type="email" v-model="form.email" required :disabled="isGoogleUser" :class="{'bg-gray-100 cursor-not-allowed text-gray-500': isGoogleUser}" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
                </div>

                <!-- SENHAS OCULTADAS PARA USUÁRIO GOOGLE -->
                <div v-if="!isGoogleUser">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha Segura</label>
                    <input type="password" v-model="form.password" :required="!isGoogleUser" minlength="8" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
                </div>
                <div v-if="!isGoogleUser">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmação de Senha</label>
                    <input type="password" v-model="form.password_confirmation" :required="!isGoogleUser" minlength="8" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors">
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

        <!-- ========================================== -->
        <!-- CHECKBOX DOS TERMOS DE USO (BLINDAGEM CLICKWRAP) -->
        <!-- ========================================== -->
        <div class="mt-6 flex items-start gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
          <div class="flex items-center h-5 mt-0.5">
            <input 
              id="termos" 
              v-model="form.aceite_termos" 
              type="checkbox" 
              class="w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer transition-colors"
              required
            >
          </div>
          <div class="text-sm">
            <label for="termos" class="font-medium text-gray-700 cursor-pointer select-none">
              Eu li, compreendi e concordo expressamente com os 
              <button type="button" @click="modalTermosAberto = true" class="text-blue-600 hover:text-blue-800 font-bold underline focus:outline-none transition-colors">
                Termos de Uso e Política de Isenção de Responsabilidade
              </button> 
              da 123FRETEI.
            </label>
          </div>
        </div>

        <div class="pt-6 flex flex-col-reverse sm:flex-row items-center justify-between border-t border-gray-100 gap-4 sm:gap-0">
            <router-link v-if="!isGoogleUser" to="/login" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                ← Voltar ao Login
            </router-link>
            <div v-else></div>
            
            <button type="submit" :disabled="loading || !form.aceite_termos" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg shadow-md disabled:opacity-50 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                {{ loading ? 'A Analisar Dados (RFB)...' : (isGoogleUser ? 'Completar Cadastro' : 'Solicitar Homologação') }}
            </button>
        </div>
      </form>

      <!-- Modal Injetado no Escopo Principal -->
      <TermosDeUsoModal :isOpen="modalTermosAberto" @close="modalTermosAberto = false" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import TermosDeUsoModal from '../Components/TermosDeUsoModal.vue';

const router = useRouter();
const authStore = useAuthStore();

// Inteligência do Google
const isGoogleUser = computed(() => authStore.isAuthenticated && authStore.user);

const modalTermosAberto = ref(false);

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    razao_social: '',
    cnpj: '',
    inscricao_estadual: '',
    aceite_termos: false // <- Campo Obrigatório de Auditoria
});

const formUnmasked = ref({
    phone: '',
    cnpj: ''
});

const loading = ref(false);
const errorMsg = ref('');

onMounted(() => {
    if (isGoogleUser.value) {
        form.value.name = authStore.user.name || '';
        form.value.email = authStore.user.email || '';
    }
});

const handleRegister = async () => {
    if (loading.value || !form.value.aceite_termos) return;

    // Validação de senha no Front-end (SÓ SE NÃO FOR GOOGLE)
    if (!isGoogleUser.value) {
        if (form.value.password !== form.value.password_confirmation) {
            errorMsg.value = 'As senhas não coincidem. Verifique e tente novamente.';
            return;
        }
    }

    loading.value = true;
    errorMsg.value = '';
    
    const payload = {
        ...form.value,
        phone: formUnmasked.value.phone,
        cnpj: formUnmasked.value.cnpj
    };

    try {
        await axios.get('/sanctum/csrf-cookie');
        const { data } = await axios.post('/api/register/embarcador', payload);
        authStore.user = data.user;
        router.push({ name: 'EmbarcadorDashboard' });
    } catch (error) {
        if (error.response?.data?.errors) {
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