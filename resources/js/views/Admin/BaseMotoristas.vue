<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Base de Motoristas</h2>
          <p class="text-sm text-gray-400">Gestão e controle da frota cadastrada na plataforma.</p>
        </div>
      </div>

      <div class="p-4 bg-gray-50 border-b border-gray-200">
        <div class="relative max-w-2xl">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          </div>
          <input 
            v-model="searchQuery" 
            type="text" 
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-medium transition-all shadow-sm" 
            placeholder="Busca inteligente por Nome, CPF, CNH, E-mail ou Telefone..."
          >
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Motorista / CPF</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contato</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="filteredMotoristas.length === 0">
            <td colspan="4" class="px-6 py-10 text-center text-gray-500 font-bold">Nenhum motorista encontrado com estes filtros.</td>
          </tr>
          <tr v-for="user in filteredMotoristas" :key="user.id" class="hover:bg-blue-50/50 transition-colors cursor-pointer" :class="{'opacity-50 bg-red-50': user.status === 'banned'}">
            
            <td class="px-6 py-4 whitespace-nowrap" @click="abrirDetalhes(user)">
              <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
              <div class="text-xs text-gray-500 font-mono mt-0.5">CPF: {{ user.motorista?.cpf || 'Não preenchido' }}</div>
              <div v-if="user.motorista?.rntrc" class="text-[10px] text-gray-400 mt-1 font-bold">RNTRC: {{ user.motorista.rntrc }}</div>
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap" @click="abrirDetalhes(user)">
               <div class="text-sm text-gray-900 font-medium">{{ user.email }}</div>
               <div class="text-xs text-gray-500 mt-0.5">{{ user.phone }}</div>
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap text-center" @click="abrirDetalhes(user)">
              <span :class="getStatusBadgeClass(user.status)">{{ user.status.toUpperCase() }}</span>
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
              <button @click.stop="abrirDetalhes(user)" class="px-3 py-1 bg-white border border-gray-300 text-gray-700 font-bold rounded shadow-sm hover:bg-gray-50 text-xs transition-colors">Dossiê</button>
              <button v-if="user.status !== 'banned'" @click.stop="alterarStatus(user, 'banned')" class="px-3 py-1 bg-red-600 text-white font-bold rounded shadow hover:bg-red-700 text-xs transition-colors">Banir</button>
              <button v-if="user.status === 'banned'" @click.stop="alterarStatus(user, 'active')" class="px-3 py-1 bg-gray-200 text-gray-800 font-bold rounded shadow hover:bg-gray-300 text-xs transition-colors">Restaurar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModalDetalhes" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="fecharDetalhes"></div>
      
      <div class="relative bg-white rounded-xl shadow-2xl max-w-3xl w-full overflow-hidden border border-gray-200">
        <div class="bg-gray-900 px-6 py-4 text-white flex justify-between items-center">
          <div>
            <h3 class="text-lg font-black uppercase tracking-tight">Dossiê Completo do Motorista</h3>
            <p class="text-xs text-gray-400 mt-1">ID Cadastro: #{{ userSelecionado?.id }}</p>
          </div>
          <button @click="fecharDetalhes" class="text-gray-400 hover:text-white font-bold text-2xl">&times;</button>
        </div>

        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
          
          <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg border border-gray-200">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-black text-xl">
                {{ userSelecionado?.name?.charAt(0).toUpperCase() }}
              </div>
              <div>
                <h4 class="font-black text-gray-900 text-lg">{{ userSelecionado?.name }}</h4>
                <p class="text-xs text-gray-500 font-mono">{{ userSelecionado?.email }}</p>
              </div>
            </div>
            <div class="text-right">
              <span :class="getStatusBadgeClass(userSelecionado?.status)" class="text-sm px-3 py-1">{{ userSelecionado?.status.toUpperCase() }}</span>
              <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold">Membro desde {{ new Date(userSelecionado?.created_at).toLocaleDateString('pt-BR') }}</p>
            </div>
          </div>

          <div>
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b pb-2">Identidade e CNH</h4>
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-white p-3 border border-gray-100 rounded shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase">CPF</span>
                <span class="text-sm font-bold text-gray-900 font-mono">{{ userSelecionado?.motorista?.cpf || 'N/A' }}</span>
              </div>
              <div class="bg-white p-3 border border-gray-100 rounded shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase">Telefone / WhatsApp</span>
                <span class="text-sm font-bold text-gray-900 font-mono">{{ userSelecionado?.phone }}</span>
              </div>
              <div class="bg-white p-3 border border-gray-100 rounded shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase">Número da CNH</span>
                <span class="text-sm font-bold text-gray-900 font-mono">{{ userSelecionado?.motorista?.cnh || 'N/A' }}</span>
              </div>
              <div class="bg-white p-3 border border-gray-100 rounded shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase">Validade da CNH</span>
                <span class="text-sm font-bold text-gray-900 font-mono" :class="{'text-red-600': isCnhVencida(userSelecionado?.motorista?.validade_cnh)}">
                  {{ userSelecionado?.motorista?.validade_cnh ? new Date(userSelecionado.motorista.validade_cnh).toLocaleDateString('pt-BR', {timeZone: 'UTC'}) : 'N/A' }}
                </span>
              </div>
            </div>
          </div>

          <div>
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b pb-2">Informações Operacionais (ANTT)</h4>
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-blue-50 p-4 border border-blue-100 rounded-lg">
                <span class="block text-[10px] font-black text-blue-400 uppercase">Registro RNTRC</span>
                <span class="text-xl font-black text-blue-900 mt-1 block font-mono">{{ userSelecionado?.motorista?.rntrc || 'Não Cadastrado' }}</span>
              </div>
              <div class="bg-purple-50 p-4 border border-purple-100 rounded-lg">
                <span class="block text-[10px] font-black text-purple-400 uppercase">Status de KYC (Auditoria)</span>
                <span class="text-lg font-black text-purple-900 mt-1 block uppercase">{{ userSelecionado?.motorista?.status_verificacao?.replace('_', ' ') || 'Pendente' }}</span>
              </div>
            </div>
          </div>

        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-200">
          <button @click="fecharDetalhes" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-6 rounded shadow-sm transition-colors">
            Fechar Dossiê
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const motoristas = ref([]);
const loading = ref(true);
const searchQuery = ref('');

// Modais
const showModalDetalhes = ref(false);
const userSelecionado = ref(null);

// Filtro Inteligente (Smart Search)
const filteredMotoristas = computed(() => {
  if (!searchQuery.value) return motoristas.value;
  
  const query = searchQuery.value.toLowerCase();
  
  return motoristas.value.filter(user => {
    return (
      user.name?.toLowerCase().includes(query) ||
      user.email?.toLowerCase().includes(query) ||
      user.phone?.includes(query) ||
      user.motorista?.cpf?.includes(query) ||
      user.motorista?.cnh?.includes(query) ||
      user.motorista?.rntrc?.includes(query) ||
      user.status?.toLowerCase().includes(query)
    );
  });
});

const getStatusBadgeClass = (status) => {
  const map = {
    active: 'bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold border border-green-200',
    pending: 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold border border-yellow-200',
    banned: 'bg-red-600 text-white px-2 py-1 rounded text-xs font-bold shadow-sm',
    em_analise: 'bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold border border-blue-200'
  };
  return map[status] || 'bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-bold';
};

const isCnhVencida = (dataValidade) => {
  if (!dataValidade) return false;
  return new Date(dataValidade) < new Date();
};

const carregarMotoristas = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/crm/motoristas');
    motoristas.value = res.data.data ? res.data.data : res.data;
  } catch (error) {
    console.error('Erro ao carregar motoristas:', error);
  } finally {
    loading.value = false;
  }
};

// Ações do Modal Dossiê
const abrirDetalhes = (user) => {
  userSelecionado.value = user;
  showModalDetalhes.value = true;
};

const fecharDetalhes = () => {
  showModalDetalhes.value = false;
  userSelecionado.value = null;
};

const alterarStatus = async (user, status) => {
  if (!confirm(`Tem certeza que deseja ${status === 'banned' ? 'BANIR' : 'RESTAURAR'} o acesso de ${user.name}?`)) return;
  
  try {
    await axios.post(`/api/admin/usuarios/${user.id}/status`, { status });
    await carregarMotoristas(); 
  } catch (error) {
    alert('Falha ao processar o comando de segurança.');
  }
};

onMounted(() => {
  carregarMotoristas();
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>