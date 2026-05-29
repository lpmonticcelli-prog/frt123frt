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
        'fixed lg:static inset-y-0 left-0 z-modal w-72 lg:w-64 bg-white border-r border-surface-200 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:shadow-none'
      ]"
    >
      <div class="h-16 flex-shrink-0 flex items-center justify-between px-6 border-b border-surface-200 pt-safe-top lg:pt-0">
        <div class="flex items-center gap-2">
          <!-- Injeção do Asset Oficial (logo1.png) e Ajuste HEX do "123" -->
          <img src="/logo1.png" alt="Logotipo 123fretei" class="h-7 w-auto object-contain shrink-0" />
          <span class="text-xl font-extrabold tracking-tight flex items-baseline">
            <span class="text-[#035D29]">123</span><span class="text-brand-600">fretei</span>
          </span>
          <span class="ml-2 text-[9px] font-black text-surface-600 bg-surface-200 px-2 py-0.5 rounded-sm uppercase tracking-widest">Driver</span>
        </div>
        <button @click="isMobileMenuOpen = false" class="lg:hidden p-1 text-surface-400 hover:text-surface-600 focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-sm">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      
      <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto scrollbar-clinical">
        <span class="block px-4 mb-3 text-[10px] font-bold text-surface-400 uppercase tracking-widest">Operação</span>
        
        <router-link :to="{ name: 'MotoristaMural' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Mural de Fretes
        </router-link>
        
        <router-link :to="{ name: 'MotoristaMeusFretes' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Meus Fretes (Alocados)
        </router-link>

        <router-link :to="{ name: 'MotoristaCarteira' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Minha Carteira
        </router-link>

        <router-link :to="{ name: 'MotoristaMeusChamados' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Central de Suporte (SAC)
        </router-link>
        
        <router-link :to="{ name: 'MotoristaPerfil' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" active-class="bg-brand-50 text-brand-700 shadow-clinical-sm font-semibold">
          Minha Conta
        </router-link>

        <div class="pt-6 mt-4 border-t border-surface-200">
          <span class="block px-4 mb-3 text-[10px] font-bold text-surface-400 uppercase tracking-widest">Hub 123fretei</span>
          
          <router-link :to="{ name: 'MotoristaLoja' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-surface-800" active-class="bg-surface-800 text-white shadow-clinical-sm font-semibold">
            Loja & EPIs
          </router-link>

          <router-link :to="{ name: 'MotoristaVoucher' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-surface-800" active-class="bg-surface-800 text-white shadow-clinical-sm font-semibold">
            Meus Vouchers
          </router-link>

          <router-link :to="{ name: 'MotoristaParceiros' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-surface-800" active-class="bg-surface-800 text-white shadow-clinical-sm font-semibold">
            Parceiros Estratégicos
          </router-link>

          <router-link :to="{ name: 'MotoristaFaq' }" class="block px-4 py-3 rounded-md text-sm font-medium transition-colors text-surface-600 hover:bg-surface-100 hover:text-surface-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-surface-800" active-class="bg-surface-800 text-white shadow-clinical-sm font-semibold">
            Central de Ajuda (FAQ)
          </router-link>
        </div>
      </nav>

      <div class="h-48 px-4 pb-4 shrink-0 mt-auto hidden lg:block border-t border-surface-200 pt-4 bg-white">
        <AdCarousel posicionamento="lateral" />
      </div>

    </aside>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
      
      <header class="h-16 sm:h-20 flex-shrink-0 bg-white border-b border-surface-200 flex items-center justify-between px-4 sm:px-6 z-sticky pt-safe-top lg:pt-0 shadow-clinical-sm">
        <div class="flex items-center gap-3">
          <button @click="isMobileMenuOpen = true" class="lg:hidden text-surface-500 hover:text-surface-800 focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-sm p-1.5 -ml-1.5">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
          </button>
          <h1 class="text-base sm:text-lg font-semibold text-surface-900 truncate max-w-[150px] sm:max-w-xs">{{ tituloPagina }}</h1>
        </div>

        <div class="hidden md:flex flex-1 max-w-md mx-6 h-12 overflow-hidden rounded-md border border-surface-200 shadow-inner bg-surface-50">
           <AdCarousel posicionamento="topo" />
        </div>

        <div class="flex items-center gap-4 shrink-0">
          <span class="hidden sm:inline text-sm text-surface-500 truncate max-w-[150px]">
            Motorista: <strong class="text-surface-800">{{ authStore.user?.name || '---' }}</strong>
          </span>
          <button @click="logout" class="text-sm font-bold text-rose-600 hover:text-rose-700 px-3 py-1.5 rounded-md hover:bg-rose-50 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500">
            Sair
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-clinical bg-surface-50 p-4 sm:p-6 lg:p-8 relative">
        <router-view v-slot="{ Component }">
          <transition 
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
            mode="out-in"
          >
            <component :is="Component" />
          </transition>
        </router-view>
      </main>

      <footer class="h-24 sm:h-28 bg-white border-t border-surface-200 flex-shrink-0 px-4 py-2 pb-safe-bottom z-sticky shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
         <AdCarousel posicionamento="rodape" />
      </footer>

    </div>
    
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth'; 

// ATENÇÃO: NOME DA PASTA COM LETRA MAIÚSCULA PARA O VITE (LINUX/WSL)
import AdCarousel from '../Components/AdCarousel.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const isMobileMenuOpen = ref(false);

const tituloPagina = computed(() => route.meta.title || 'Painel do Motorista');

watch(() => route.path, () => {
  isMobileMenuOpen.value = false;
});

const logout = async () => {
    try {
        await authStore.logout(); 
        router.push({ name: 'Login' });
    } catch (error) {
        console.error('[AUTH] Sincronização falhou no logout.', error);
        localStorage.clear();
        sessionStorage.clear();
        window.location.href = '/login';
    }
};
</script>