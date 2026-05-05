<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-md border border-gray-100">
      <div>
        <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
          Cadastro de Motorista
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Encontre fretes e aumente sua renda.
        </p>
      </div>
      
      <div v-if="errorMessage" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 text-sm text-red-700">
        {{ errorMessage }}
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="register">
        <div class="rounded-md shadow-sm space-y-4">
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Nome Completo</label>
            <input v-model="form.name" type="text" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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

          <div class="border-t border-gray-100 pt-4 mt-4">
            <label class="block text-sm font-medium text-gray-700">E-mail</label>
            <input v-model="form.email" type="email" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Senha</label>
              <input v-model="form.password" type="password" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Confirmação de Senha</label>
              <input v-model="form.password_confirmation" type="password" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
          </div>
        </div>

        <div>
          <button type="submit" :disabled="loading" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-colors">
            {{ loading ? 'Criando conta...' : 'Finalizar Cadastro' }}
          </button>
        </div>
        
        <div class="text-center mt-4">
          <router-link :to="{ name: 'Login' }" class="font-medium text-sm text-blue-600 hover:text-blue-500">
            Já tem uma conta? Faça login
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

const router = useRouter();
const authStore = useAuthStore();

// O objeto reativo para visualização
const form = ref({
  name: '',
  cpf: '',
  phone: '',
  email: '',
  password: '',
  password_confirmation: '',
  cnh: '',
  validade_cnh: '',
  rntrc: ''
});

// O objeto reativo para armazenar os dados limpos sem formatação
const formUnmasked = ref({
  cpf: '',
  phone: '',
  rntrc: ''
});

const loading = ref(false);
const errorMessage = ref('');

const register = async () => {
  // Validação rápida no Front-end para evitar tráfego inútil
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'As senhas não coincidem.';
    return;
  }

  loading.value = true;
  errorMessage.value = '';

  // Monta o payload substituindo as versões com máscara pelas versões limpas
  const payload = {
    ...form.value,
    cpf: formUnmasked.value.cpf,
    phone: formUnmasked.value.phone,
    rntrc: formUnmasked.value.rntrc
  };

  try {
    // 1. Injeta o cookie de proteção CSRF do Laravel
    await axios.get('/sanctum/csrf-cookie');
    
    // 2. Envia a requisição (o backend fará a validação matemática do CPF)
    const { data } = await axios.post('/api/register/motorista', payload);
    
    // 3. Atualiza o estado global apenas com os dados do utilizador (Cookies são gerenciados pelo browser)
    authStore.user = data.user;
    
    // 4. Redirecionamento seguro para a dashboard do Motorista
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