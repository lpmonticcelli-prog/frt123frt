<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-md border border-gray-100 relative">
      <div>
        <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
          {{ isGoogleUser ? 'Complete seu Perfil' : 'Cadastro de Motorista' }}
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          {{ isGoogleUser ? 'Falta pouco! Preencha seus documentos para começar.' : 'Encontre fretes e aumente sua renda.' }}
        </p>
      </div>
      
      <div v-if="errorMessage" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 text-sm text-red-700">
        {{ errorMessage }}
      </div>

      <!-- BOTÃO DO GOOGLE (Some se já estiver logado pelo Google) -->
      <div v-if="!isGoogleUser" class="mt-6">
        <a href="/api/auth/google/redirect" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-semibold rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
            </svg>
            Cadastrar com o Google
        </a>
      </div>

      <div v-if="!isGoogleUser" class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500 font-medium">Ou cadastre com e-mail</span>
        </div>
      </div>

      <form class="space-y-6" @submit.prevent="register">
        <div class="rounded-md shadow-sm space-y-4">
          
          <!-- NOME -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Nome Completo</label>
            <input v-model="form.name" type="text" required :disabled="isGoogleUser" :class="{'bg-gray-100 cursor-not-allowed text-gray-500': isGoogleUser}" class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>

          <!-- EMAIL -->
          <div>
            <label class="block text-sm font-medium text-gray-700">E-mail</label>
            <input v-model="form.email" type="email" required :disabled="isGoogleUser" :class="{'bg-gray-100 cursor-not-allowed text-gray-500': isGoogleUser}" class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">CPF</label>
              <input 
                v-model="form.cpf" 
                v-maska
                data-maska="###.###.###-##"
                @maska="formUnmasked.cpf = $event.detail.unmasked"
                placeholder="000.000.000-00"
                type="text" 
                required 
                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Telefone / WhatsApp</label>
              <input 
                v-model="form.phone" 
                v-maska
                data-maska="(##) #####-####"
                @maska="formUnmasked.phone = $event.detail.unmasked"
                placeholder="(11) 99999-9999"
                type="text" 
                required 
                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 mt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Número da CNH</label>
              <input v-model="form.cnh" type="text" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Validade da CNH</label>
              <input v-model="form.validade_cnh" type="date" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">RNTRC (Registro ANTT)</label>
            <input 
                v-model="form.rntrc" 
                v-maska
                data-maska="########"
                @maska="formUnmasked.rntrc = $event.detail.unmasked"
                placeholder="00000000"
                type="text" 
                required 
                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
          </div>

          <!-- SENHAS (Somem se já estiver logado pelo Google) -->
          <div v-if="!isGoogleUser" class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 mt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Senha</label>
              <input v-model="form.password" type="password" :required="!isGoogleUser" class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Confirmação</label>
              <input v-model="form.password_confirmation" type="password" :required="!isGoogleUser" class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- CHECKBOX DOS TERMOS DE USO (BLINDAGEM CLICKWRAP) -->
        <!-- ========================================== -->
        <div class="mt-6 mb-4 flex items-start gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
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

        <div>
          <!-- Botão Bloqueado se a Checkbox não estiver marcada -->
          <button type="submit" :disabled="loading || !form.aceite_termos" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-colors cursor-pointer disabled:cursor-not-allowed">
            {{ loading ? 'Processando...' : (isGoogleUser ? 'Completar Perfil e Entrar' : 'Finalizar Cadastro') }}
          </button>
        </div>
        
        <div v-if="!isGoogleUser" class="text-center mt-4">
          <router-link :to="{ name: 'Login' }" class="font-medium text-sm text-blue-600 hover:text-blue-500">
            Já tem uma conta? Faça login
          </router-link>
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
import { useAuthStore } from '../stores/auth';
import axios from 'axios';
import TermosDeUsoModal from '../Components/TermosDeUsoModal.vue';

const router = useRouter();
const authStore = useAuthStore();

// Inteligência que detecta se o usuário veio do Google
const isGoogleUser = computed(() => authStore.isAuthenticated && authStore.user);

const modalTermosAberto = ref(false);

const form = ref({
  name: '',
  cpf: '',
  phone: '',
  email: '',
  password: '',
  password_confirmation: '',
  cnh: '',
  validade_cnh: '',
  rntrc: '',
  aceite_termos: false // <- Campo Obrigatório de Auditoria
});

const formUnmasked = ref({
  cpf: '',
  phone: '',
  rntrc: ''
});

const loading = ref(false);
const errorMessage = ref('');

onMounted(() => {
    // Se veio do Google, preenche automaticamente os campos que já temos
    if (isGoogleUser.value) {
        form.value.name = authStore.user.name || '';
        form.value.email = authStore.user.email || '';
    }
});

const register = async () => {
  if (loading.value || !form.value.aceite_termos) return;

  // Só checa a senha se NÃO for do Google
  if (!isGoogleUser.value) {
      if (form.value.password !== form.value.password_confirmation) {
        errorMessage.value = 'As senhas não coincidem.';
        return;
      }
  }

  loading.value = true;
  errorMessage.value = '';

  const payload = {
    ...form.value,
    cpf: formUnmasked.value.cpf,
    phone: formUnmasked.value.phone,
    rntrc: formUnmasked.value.rntrc
  };

  try {
    await axios.get('/sanctum/csrf-cookie');
    const { data } = await axios.post('/api/register/motorista', payload);
    authStore.user = data.user;
    
    router.push({ name: 'MotoristaDashboard' });
  } catch (error) {
    if (error.response?.data?.errors) {
      errorMessage.value = Object.values(error.response.data.errors)[0][0];
    } else {
      errorMessage.value = error.response?.data?.message || 'Erro ao realizar o cadastro.';
    }
  } finally {
    loading.value = false;
  }
};
</script>