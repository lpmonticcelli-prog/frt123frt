<template>
  <div class="flex h-screen bg-gray-100 text-gray-900 font-sans">
    
    <aside class="w-64 bg-gray-900 text-white flex flex-col shadow-lg z-10 transition-all duration-300">
      <div class="h-16 flex items-center px-6 border-b border-gray-800 bg-gray-950 select-none">
        <span class="text-xl font-black text-blue-500 tracking-wider">123fretei</span>
        <span class="ml-2 text-[10px] font-bold text-gray-300 bg-red-600 px-2 py-0.5 rounded uppercase tracking-widest">Backoffice</span>
      </div>
      
      <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar">
        <div v-for="item in menuFiltrado" :key="item.id" class="mb-2">
          
          <router-link 
            v-if="!item.submenus" 
            :to="{ name: item.route }" 
            class="flex items-center w-full px-4 py-2.5 rounded text-sm font-medium transition-colors hover:bg-gray-800 text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            active-class="bg-blue-600 text-white shadow-md font-bold"
          >
            {{ item.label }}
          </router-link>

          <div v-else>
            <button 
              @click="toggleMenu(item.id)" 
              class="flex items-center justify-between w-full px-4 py-2.5 rounded text-sm font-medium transition-colors hover:bg-gray-800 text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'bg-gray-800 text-white': menuAberto === item.id }"
              :aria-expanded="menuAberto === item.id"
            >
              <div class="flex items-center">
                {{ item.label }}
              </div>
              <svg 
                class="w-4 h-4 transition-transform duration-200" 
                :class="{ 'rotate-180': menuAberto === item.id }" 
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>

            <div 
              v-show="menuAberto === item.id" 
              class="pl-4 pr-4 py-1 mt-1 space-y-1 border-l-2 border-gray-700 ml-2"
            >
              <router-link 
                v-for="sub in submenusFiltrados(item.submenus)" 
                :key="sub.route" 
                :to="{ name: sub.route }" 
                class="block px-2 py-1.5 rounded text-xs font-medium text-gray-400 hover:text-white hover:bg-gray-800 transition-colors focus:outline-none focus:ring-1 focus:ring-blue-500"
                active-class="text-blue-400 font-bold bg-gray-800"
              >
                {{ sub.label }}
              </router-link>
            </div>
          </div>

        </div>
      </nav>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 shadow-sm">
        <h1 class="text-lg font-black text-gray-800">{{ tituloPagina }}</h1>
        <div class="flex items-center space-x-4">
          <div class="text-right">
            <p class="text-sm text-gray-900 font-bold">{{ userName }}</p>
            <p class="text-[10px] text-blue-600 font-black uppercase">{{ userRoleName }}</p>
          </div>
          <button 
            @click="handleLogout" 
            :disabled="isLoggingOut"
            class="text-sm text-red-600 hover:text-red-800 font-bold px-3 py-1 border border-red-100 rounded bg-red-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-red-500"
          >
            {{ isLoggingOut ? 'Processando...' : 'Sair do Sistema' }}
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-8">
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

interface SubMenu {
  label: string;
  route: string;
  roles: string[];
}

interface MenuItem {
  id: string;
  label: string;
  route?: string;
  roles: string[];
  submenus?: SubMenu[];
}

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const tituloPagina = computed<string>(() => (route.meta?.title as string) || 'Centro de Comando');
const userRole = computed<string | undefined>(() => authStore.user?.role?.slug);
const userName = computed<string>(() => authStore.user?.name || 'Carregando...');
const userRoleName = computed<string>(() => authStore.user?.role?.name || '');

const menuAberto = ref<string | null>('ops');
const isLoggingOut = ref<boolean>(false);

// Error Boundary para evitar WSOD em falhas de injeção da router-view
onErrorCaptured((err: unknown, instance: any, info: string) => {
  console.error('[AdminLayout] Erro interceptado na árvore de componentes:', err, info);
  return false; 
});

const toggleMenu = (id: string): void => {
  menuAberto.value = menuAberto.value === id ? null : id;
};

const menuItens: MenuItem[] = [
  {
    id: 'dash',
    label: 'Centro de Comando',
    route: 'AdminDashboard',
    roles: ['admin', 'manager']
  },
  {
    id: 'ops',
    label: 'Mesa de Operações',
    roles: ['admin', 'manager', 'suporte_n1', 'compliance'],
    submenus: [
      { label: 'SAC N1 (Atendimento)', route: 'AdminSuporte', roles: ['admin', 'manager', 'suporte_n1'] },
      { label: 'Mural de Fretes', route: 'AdminFretes', roles: ['admin', 'manager'] },
      { label: 'Resolução de Disputas', route: 'AdminDisputas', roles: ['admin', 'manager', 'compliance'] },
      { label: 'Arquivo Morto (Auditoria)', route: 'AdminHistoricoFretes', roles: ['admin', 'manager', 'compliance'] }
    ]
  },
  {
    id: 'crm',
    label: 'CRM & Identidade',
    roles: ['admin', 'compliance'],
    submenus: [
      { label: 'Auditoria KYC', route: 'AdminAuditoria', roles: ['admin', 'compliance'] },
      { label: 'Base de Motoristas', route: 'AdminMotoristas', roles: ['admin'] },
      { label: 'Base de Embarcadores', route: 'AdminEmbarcadores', roles: ['admin'] },
      { label: 'Rede de Parceiros', route: 'AdminParceiros', roles: ['admin'] } 
    ]
  },
  {
    id: 'fin',
    label: 'Financeiro',
    roles: ['admin'],
    submenus: [
      { label: 'Extrato & Taxas', route: 'AdminExtrato', roles: ['admin'] },
      { label: 'Faturamento', route: 'AdminFaturamento', roles: ['admin'] }
    ]
  },
  {
    id: 'sys',
    label: 'Configurações Core',
    roles: ['admin'],
    submenus: [
      { label: 'Staff & Permissões', route: 'AdminStaff', roles: ['admin'] },
      { label: 'Variáveis Globais', route: 'AdminConfig', roles: ['admin'] },
      { label: 'Integrações & APIs', route: 'AdminParceirosApi', roles: ['admin'] }
    ]
  }
];

const menuFiltrado = computed<MenuItem[]>(() => {
  if (!userRole.value) return [];
  return menuItens.filter(item => item.roles.includes(userRole.value as string));
});

const submenusFiltrados = (submenus?: SubMenu[]): SubMenu[] => {
  if (!userRole.value || !submenus) return [];
  return submenus.filter(sub => sub.roles.includes(userRole.value as string));
};

const handleLogout = async (): Promise<void> => {
  if (isLoggingOut.value) return;
  isLoggingOut.value = true;
  
  try {
    await authStore.logout(); 
    await router.push({ name: 'Login' });
  } catch (error) {
    console.error('[AdminLayout] Falha ao processar logout no backend:', error);
    // Neutraliza retenção de estado na máquina do cliente em caso de falha da API
    localStorage.clear();
    sessionStorage.clear();
    window.location.href = '/login';
  } finally {
    isLoggingOut.value = false;
  }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #374151;
  border-radius: 4px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
  background: #4B5563;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>