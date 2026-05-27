<template>
  <div class="flex h-screen bg-surface-50 text-surface-900 font-sans overflow-hidden selection:bg-brand-500 selection:text-white">
    
    <aside class="w-64 bg-white border-r border-surface-200 flex flex-col shadow-clinical-sm z-20 shrink-0">
      <div class="h-16 flex items-center px-6 border-b border-surface-200 select-none shrink-0">
        <span class="text-xl font-extrabold text-brand-600 tracking-tight">123<span class="text-surface-900">fretei</span></span>
        <span class="ml-2 text-[10px] font-bold text-surface-500 bg-surface-100 px-2 py-1 rounded uppercase tracking-wider">Indústria</span>
      </div>
      
      <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto scrollbar-clinical">
        <router-link :to="{ name: 'EmbarcadorDashboard' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-surface-900" active-class="bg-surface-900 text-white hover:bg-surface-800 shadow-clinical-sm font-bold">
          Mural de Cargas
        </router-link>
        
        <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-surface-900" active-class="bg-surface-900 text-white hover:bg-surface-800 shadow-clinical-sm font-bold">
          Publicar Novo Frete
        </router-link>

        <router-link :to="{ name: 'EmbarcadorFaturas' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-surface-900" active-class="bg-surface-900 text-white hover:bg-surface-800 shadow-clinical-sm font-bold">
          Minhas Faturas
        </router-link>

        <router-link :to="{ name: 'EmbarcadorMeusChamados' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-surface-900" active-class="bg-surface-900 text-white hover:bg-surface-800 shadow-clinical-sm font-bold">
          Central de Suporte (SAC)
        </router-link>
        
        <router-link :to="{ name: 'EmbarcadorPerfil' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 mt-8 focus:outline-none focus:ring-2 focus:ring-surface-900" active-class="bg-surface-900 text-white hover:bg-surface-800 shadow-clinical-sm font-bold">
          Minha Conta
        </router-link>
      </nav>

      <div class="h-48 px-4 pb-4 shrink-0 mt-auto hidden lg:block border-t border-surface-200 pt-4 bg-white">
        <AdCarousel posicionamento="lateral" />
      </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
      
      <header class="h-16 bg-white border-b border-surface-200 flex items-center justify-between px-6 sm:px-8 shadow-clinical-sm shrink-0 z-10">
        <h1 class="text-base sm:text-xl font-bold text-surface-800 truncate max-w-[150px] sm:max-w-xs">{{ tituloPagina }}</h1>
        
        <div class="hidden md:flex flex-1 max-w-md mx-6 h-12 overflow-hidden rounded-md border border-surface-200 shadow-inner bg-surface-50">
           <AdCarousel posicionamento="topo" />
        </div>

        <div class="flex items-center space-x-4 sm:space-x-6 shrink-0">
          <div class="flex items-center">
            <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-black text-sm mr-3 select-none" aria-hidden="true">
              {{ userInitial }}
            </div>
            <span class="hidden sm:inline text-sm text-surface-600">
              Embarcador: <strong class="text-surface-900">{{ userName }}</strong>
            </span>
          </div>
          
          <div class="h-6 w-px bg-surface-200 hidden sm:block"></div> 
          
          <button 
            @click="handleLogout" 
            :disabled="isLoggingOut"
            class="text-sm text-rose-600 hover:text-rose-800 font-bold transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-rose-500 rounded p-1"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            {{ isLoggingOut ? 'Saindo...' : 'Sair' }}
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-x-hidden overflow-y-auto scrollbar-clinical bg-surface-50 p-4 sm:p-8 relative">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>

      <footer class="h-24 sm:h-28 bg-white border-t border-surface-200 flex-shrink-0 px-4 py-2 z-10 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
         <AdCarousel posicionamento="rodape" />
      </footer>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onErrorCaptured } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AdCarousel from '../Components/AdCarousel.vue';

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
.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px;}
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>