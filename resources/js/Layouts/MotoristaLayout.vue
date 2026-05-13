<template>
  <div class="flex h-screen bg-gray-50 text-gray-900 font-sans">
    
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
      <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <span class="text-xl font-bold text-blue-700">123fretei</span>
        <span class="ml-2 text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded">Driver</span>
      </div>
      
      <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
        <span class="block px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Operação</span>
        
        <router-link :to="{ name: 'MotoristaMural' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-blue-50 text-blue-700">
          Mural de Fretes
        </router-link>
        
        <router-link :to="{ name: 'MotoristaMeusFretes' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-blue-50 text-blue-700">
          Meus Fretes (Alocados)
        </router-link>

        <router-link :to="{ name: 'MotoristaCarteira' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-blue-50 text-blue-700">
          Minha Carteira
        </router-link>

        <router-link :to="{ name: 'MotoristaMeusChamados' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-blue-50 text-blue-700">
          Central de Suporte (SAC)
        </router-link>
        
        <router-link :to="{ name: 'MotoristaPerfil' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-blue-50 text-blue-700">
          Minha Conta
        </router-link>

        <div class="pt-6 mt-2">
          <span class="block px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Hub 123fretei</span>
          
          <router-link :to="{ name: 'MotoristaLoja' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-emerald-50 text-emerald-700">
            Loja & EPIs
          </router-link>

          <router-link :to="{ name: 'MotoristaVoucher' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-emerald-50 text-emerald-700">
            Meus Vouchers
          </router-link>

          <router-link :to="{ name: 'MotoristaParceiros' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-emerald-50 text-emerald-700">
            Parceiros Estratégicos
          </router-link>

          <router-link :to="{ name: 'MotoristaFaq' }" class="block px-4 py-2 rounded text-sm font-medium transition-colors hover:bg-gray-100" active-class="bg-emerald-50 text-emerald-700">
            Central de Ajuda (FAQ)
          </router-link>
        </div>
      </nav>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8">
        <h1 class="text-lg font-semibold text-gray-800">{{ tituloPagina }}</h1>
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-600">Motorista: <strong>{{ authStore.user?.name || 'Carregando...' }}</strong></span>
          <button @click="logout" class="text-sm text-red-600 hover:text-red-800 font-medium">Sair da Conta</button>
        </div>
      </header>

      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-8">
        <router-view />
      </main>
    </div>
    
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const tituloPagina = computed(() => route.meta.title || 'Painel do Motorista');

const logout = async () => {
    await authStore.logout(); 
    router.push({ name: 'Login' });
};
</script>