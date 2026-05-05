<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar Dinâmica do Hub -->
    <nav class="bg-slate-900 shadow-md border-b border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <!-- Branding -->
            <div class="flex-shrink-0 flex items-center gap-3">
              <div class="w-8 h-8 bg-emerald-500 rounded flex items-center justify-center font-bold text-slate-900">
                123
              </div>
              <span class="text-white font-bold text-xl tracking-tight hidden sm:block">
                VIWE SyS <span class="text-emerald-400">Hub</span>
              </span>
            </div>
          </div>

          <!-- Ações do Usuário -->
          <div class="flex items-center gap-4">
            <!-- Botão Dinâmico de Retorno -->
            <router-link 
              :to="dashboardRoute"
              class="text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-md transition border border-slate-700"
            >
              &larr; Voltar ao Painel
            </router-link>

            <!-- Menu de Perfil / Logout -->
            <button @click="handleLogout" class="text-slate-400 hover:text-red-400 transition" title="Sair do Sistema">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Sub-Header Contextual -->
    <header class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-lg font-semibold text-slate-800">
          {{ currentRouteName }}
        </h1>
        <div class="text-sm text-slate-500">
          Operador: <span class="font-medium text-slate-700 uppercase">{{ auth.user?.role?.slug || 'SISTEMA' }}</span>
        </div>
      </div>
    </header>

    <!-- Ponto de Injeção das Views (Faq, Loja, Parceiros, Voucher) -->
    <main class="flex-1 max-w-7xl w-full mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Transição suave entre as abas do Hub -->
      <router-view v-slot="{ Component }">
        <transition 
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
          mode="out-in"
        >
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth'; // Ajuste o caminho se a sua store estiver em outro diretório

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

// Computa o título da página baseado no meta da rota
const currentRouteName = computed(() => {
  return route.meta.title || 'Serviços Compartilhados';
});

// Computa a rota de volta de forma inteligente, dependendo de quem está logado
const dashboardRoute = computed(() => {
  const role = auth.user?.role?.slug;
  if (!role) return '/login';
  
  const staffRoles = ['admin', 'manager', 'compliance', 'suporte_n1'];
  if (staffRoles.includes(role)) {
    return '/admin/dashboard';
  }
  
  return `/${role}/painel`;
});

// Operação segura de Logout
const handleLogout = async () => {
  try {
    await auth.logout();
    router.push('/login');
  } catch (error) {
    console.error('[AUTH] Falha ao encerrar sessão.', error);
  }
};
</script>