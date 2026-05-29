<template>
  <div class="flex h-[100dvh] w-full bg-surface-50 text-surface-900 font-sans overflow-hidden selection:bg-brand-500 selection:text-white">
    
    <transition
      enter-active-class="transition-opacity ease-linear duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity ease-linear duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isMobileMenuOpen" 
        @click="isMobileMenuOpen = false"
        class="fixed inset-0 bg-surface-950/60 z-modal-backdrop lg:hidden backdrop-blur-sm"
        aria-hidden="true"
      ></div>
    </transition>

    <aside 
      :class="[
        isMobileMenuOpen ? 'translate-x-0 shadow-clinical-lg' : '-translate-x-full',
        'fixed lg:static inset-y-0 left-0 z-modal w-72 lg:w-64 bg-white border-r border-surface-200 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:shadow-none shrink-0'
      ]"
    >
      <div class="h-16 flex-shrink-0 flex items-center justify-between px-6 border-b border-surface-200 pt-safe-top lg:pt-0 gap-2">
        <div class="flex items-center gap-2">
          <img src="/logo1.png" alt="Logotipo 123fretei" class="h-7 w-auto object-contain shrink-0" />
          <span class="text-xl font-extrabold tracking-tight flex items-baseline">
            <span class="text-[#035D29]">123</span><span class="text-brand-600">fretei</span>
          </span>
          <span class="ml-2 text-[9px] font-black text-surface-600 bg-surface-200 px-2 py-0.5 rounded-sm uppercase tracking-widest hidden sm:inline-block">Indústria</span>
        </div>
        
        <button @click="isMobileMenuOpen = false" class="lg:hidden p-1 text-surface-400 hover:text-surface-600 focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-sm">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      
      <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto scrollbar-clinical">
        <span class="block px-4 mb-3 text-[10px] font-bold text-surface-400 uppercase tracking-widest">Operação</span>

        <router-link :to="{ name: 'EmbarcadorDashboard' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Mural de Cargas
        </router-link>
        
        <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Publicar Novo Frete
        </router-link>

        <router-link :to="{ name: 'EmbarcadorFaturas' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Minhas Faturas
        </router-link>

        <router-link :to="{ name: 'EmbarcadorMeusChamados' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Central de Suporte (SAC)
        </router-link>
        
        <div class="pt-6 mt-4 border-t border-surface-200">
          <span class="block px-4 mb-3 text-[10px] font-bold text-surface-400 uppercase tracking-widest">Configurações</span>
          
          <router-link :to="{ name: 'EmbarcadorPerfil' }" class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-surface-100 focus:outline-none focus:ring-2 focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
            Minha Conta
          </router-link>
        </div>
      </nav>

      <div class="h-48 px-4 pb-4 shrink-0 mt-auto hidden lg:block border-t border-surface-200 pt-4 bg-white">
        <AdCarousel posicionamento="lateral" />
      </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
      
      <header class="h-16 sm:h-20 bg-white border-b border-surface-200 flex items-center justify-between px-4 sm:px-6 shadow-clinical-sm shrink-0 z-sticky pt-safe-top lg:pt-0">
        <div class="flex items-center gap-3">
          <button @click="isMobileMenuOpen = true" class="lg:hidden text-surface-500 hover:text-surface-800 focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-sm p-1.5 -ml-1.5">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
          </button>
          <h1 class="text-base sm:text-xl font-bold text-surface-800 truncate max-w-[150px] sm:max-w-xs">{{ tituloPagina }}</h1>
        </div>
        
        <div class="hidden md:flex flex-1 max-w-md mx-6 h-12 overflow-hidden rounded-md border border-surface-200 shadow-inner bg-surface-50">
           <AdCarousel posicionamento="topo" />
        </div>

        <div class="flex items-center space-x-4 sm:space-x-6 shrink-0">
          <div class="flex items-center">
            <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-black text-sm sm:mr-3 select-none" aria-hidden="true">
              {{ userInitial }}
            </div>
            <span class="hidden sm:inline text-sm text-surface-600 truncate max-w-[150px]">
              Embarcador: <strong class="text-surface-900">{{ userName }}</strong>
            </span>
          </div>
          
          <div class="h-6 w-px bg-surface-200 hidden sm:block"></div> 
          
          <button 
            @click="handleLogout" 
            :disabled="isLoggingOut"
            class="text-sm text-rose-600 hover:text-rose-800 font-bold transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-rose-500 rounded p-1"
          >
            <svg class="hidden sm:inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            {{ isLoggingOut ? 'Saindo...' : 'Sair' }}
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-x-hidden overflow-y-auto scrollbar-clinical bg-surface-50 p-4 sm:p-8 relative">
        <router-view v-slot="{ Component, route }">
          <transition name="fade" mode="out-in">
            <div :key="route.path" class="w-full">
              <component :is="Component" />
            </div>
          </transition>
        </router-view>
      </main>

      <footer class="h-24 sm:h-28 bg-white border-t border-surface-200 flex-shrink-0 px-4 py-2 pb-safe-bottom z-10 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
         <AdCarousel posicionamento="rodape" />
      </footer>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onErrorCaptured } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AdCarousel from '../Components/AdCarousel.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

// Gerenciamento de Estado Mobile
const isMobileMenuOpen = ref<boolean>(false);
const isLoggingOut = ref<boolean>(false);

// Fechar menu mobile automaticamente ao navegar
watch(() => route.path, () => {
  isMobileMenuOpen.value = false;
});

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
/* Transições globais já declaradas em app.css, mantidas aqui apenas para fallback seguro */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>