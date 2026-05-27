<template>
  <div class="flex h-screen bg-gray-50 text-gray-900 font-sans">
    
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col shadow-sm z-10">
      <div class="h-16 flex items-center px-6 border-b border-gray-200 select-none">
        <span class="text-xl font-extrabold text-slate-900 tracking-tight">123<span class="text-orange-600">fretei</span></span>
        <span class="ml-2 text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded uppercase tracking-wider">Indústria</span>
      </div>
      
      <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <router-link :to="{ name: 'EmbarcadorDashboard' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-slate-900" active-class="bg-slate-900 text-white hover:bg-slate-800 shadow-sm">
          Mural de Cargas
        </router-link>
        
        <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-slate-900" active-class="bg-slate-900 text-white hover:bg-slate-800 shadow-sm">
          Publicar Novo Frete
        </router-link>

        <router-link :to="{ name: 'EmbarcadorFaturas' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-slate-900" active-class="bg-slate-900 text-white hover:bg-slate-800 shadow-sm">
          Minhas Faturas
        </router-link>

        <router-link :to="{ name: 'EmbarcadorMeusChamados' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-slate-900" active-class="bg-slate-900 text-white hover:bg-slate-800 shadow-sm">
          Central de Suporte (SAC)
        </router-link>
        
        <router-link :to="{ name: 'EmbarcadorPerfil' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-gray-100 mt-8 focus:outline-none focus:ring-2 focus:ring-slate-900" active-class="bg-slate-900 text-white hover:bg-slate-800 shadow-sm">
          Minha Conta
        </router-link>
      </nav>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
      
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 shadow-sm z-0">
        <h1 class="text-xl font-bold text-gray-800">{{ tituloPagina }}</h1>
        
        <div class="flex items-center space-x-6">
          <div class="flex items-center">
            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-sm mr-3 select-none" aria-hidden="true">
              {{ userInitial }}
            </div>
            <span class="text-sm text-gray-600">
              Embarcador: <strong class="text-slate-900">{{ userName }}</strong>
            </span>
          </div>
          
          <div class="h-6 w-px bg-gray-200"></div> 
          
          <button 
            @click="handleLogout" 
            :disabled="isLoggingOut"
            class="text-sm text-red-600 hover:text-red-800 font-bold transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-red-500 rounded p-1"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            {{ isLoggingOut ? 'Saindo...' : 'Sair' }}
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-8">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onErrorCaptured } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const isLoggingOut = ref<boolean>(false);

onErrorCaptured((err: unknown, instance: any, info: string) => {
  console.error('[EmbarcadorLayout] Erro interceptado na árvore de componentes:', err, info);
  return false; 
});

const tituloPagina = computed<string>(() => (route.meta?.title as string) || 'Painel de Controle');

const userName = computed<string>(() => authStore.user?.name || 'Carregando...');

const userInitial = computed<string>(() => {
  const name = authStore.user?.name;
  if (!name || typeof name !== 'string') return 'E';
  const trimmed = name.trim();
  return trimmed.length > 0 ? trimmed.charAt(0).toUpperCase() : 'E';
});

const handleLogout = async (): Promise<void> => {
  if (isLoggingOut.value) return;
  isLoggingOut.value = true;

  try {
    await authStore.logout(); 
    await router.push({ name: 'Login' });
  } catch (error) {
    console.error('[EmbarcadorLayout] Erro crítico ao destruir sessão:', error);
    localStorage.clear();
    sessionStorage.clear();
    window.location.href = '/login';
  } finally {
    isLoggingOut.value = false;
  }
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>