<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Base de Embarcadores</h2>
          <p class="text-sm text-gray-400">Gestão e controle de indústrias e transportadoras parceiras.</p>
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
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Razão Social / CNPJ</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contato (Responsável)</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="embarcadores?.length === 0">
            <td colspan="4" class="px-6 py-10 text-center text-gray-500 font-bold">Nenhum embarcador encontrado na base de dados.</td>
          </tr>
          <tr v-for="user in embarcadores" :key="user.id" class="hover:bg-gray-50" :class="{'opacity-50 bg-red-50': user.status === 'banned'}">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-gray-900">{{ user.embarcador?.razao_social || 'Não preenchido' }}</div>
              <div class="text-xs text-gray-500">CNPJ: {{ user.embarcador?.cnpj || 'Não preenchido' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
               <div class="text-sm text-gray-900">{{ user.name }}</div>
               <div class="text-xs text-gray-500">{{ user.email }} • {{ user.phone }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusBadgeClass(user.status)">{{ user.status.toUpperCase() }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
              <button v-if="user.status !== 'banned'" @click="alterarStatus(user, 'banned')" class="px-3 py-1 bg-red-600 text-white font-bold rounded shadow hover:bg-red-700 text-xs transition-colors">Banir</button>
              <button v-if="user.status === 'banned'" @click="alterarStatus(user, 'active')" class="px-3 py-1 bg-gray-200 text-gray-800 font-bold rounded shadow hover:bg-gray-300 text-xs transition-colors">Restaurar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const embarcadores = ref([]);
const loading = ref(true);

const getStatusBadgeClass = (status) => {
  const map = {
    active: 'bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold',
    pending: 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold',
    banned: 'bg-red-600 text-white px-2 py-1 rounded text-xs font-bold',
    em_analise: 'bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold'
  };
  return map[status] || 'bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-bold';
};

const carregarEmbarcadores = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/crm/embarcadores');
    embarcadores.value = res.data;
  } catch (error) {
    console.error('Erro ao carregar embarcadores:', error);
  } finally {
    loading.value = false;
  }
};

const alterarStatus = async (user, status) => {
  if (!confirm(`Tem certeza que deseja ${status === 'banned' ? 'BANIR' : 'RESTAURAR'} o acesso desta indústria/transportadora?`)) return;
  
  try {
    await axios.post(`/api/admin/usuarios/${user.id}/status`, { status });
    await carregarEmbarcadores(); 
  } catch (error) {
    alert('Falha ao processar o comando de segurança.');
  }
};

onMounted(() => {
  carregarEmbarcadores();
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>