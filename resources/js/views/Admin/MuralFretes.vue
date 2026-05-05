<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Mural de Fretes (Radar Operacional)</h2>
          <p class="text-sm text-gray-400">Monitoramento em tempo real de todas as cargas ativas na plataforma.</p>
        </div>
        <button @click="carregarCargas" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded shadow transition-colors">
          Atualizar Radar
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Publicação</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Rota / Produto</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Atores (EMB / MOT)</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Valor Frete</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="cargas?.length === 0">
            <td colspan="5" class="px-6 py-10 text-center text-gray-500 font-bold">Nenhuma carga ativa no momento. Radar limpo.</td>
          </tr>
          <tr v-for="carga in cargas" :key="carga.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ new Date(carga.created_at).toLocaleDateString('pt-BR') }}
              <div class="text-xs text-gray-400">{{ new Date(carga.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-gray-900">{{ carga.cidade_origem }}/{{ carga.uf_origem }} ➔ {{ carga.cidade_destino }}/{{ carga.uf_destino }}</div>
              <div class="text-xs text-gray-500">{{ carga.produto }} ({{ carga.peso_kg }}kg)</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-xs">
              <div><span class="font-bold text-gray-400">EMB:</span> {{ carga.embarcador?.razao_social || 'N/A' }}</div>
              <div v-if="carga.motorista"><span class="font-bold text-gray-400">MOT:</span> {{ carga.motorista?.user?.name || 'Não alocado' }}</div>
              <div v-else><span class="font-bold text-yellow-600">Aguardando Motorista</span></div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs font-bold rounded-md uppercase" :class="getStatusClass(carga.status)">
                {{ carga.status?.replace('_', ' ') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <div class="text-sm font-bold text-gray-900">{{ formatMoney(carga.valor_frete) }}</div>
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

const cargas = ref([]);
const loading = ref(true);

const formatMoney = (value) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const getStatusClass = (status) => {
  const map = {
    publicada: 'bg-blue-100 text-blue-800',
    aceita: 'bg-indigo-100 text-indigo-800',
    em_transito: 'bg-yellow-100 text-yellow-800',
    coletada: 'bg-purple-100 text-purple-800'
  };
  return map[status] || 'bg-gray-100 text-gray-800';
};

const carregarCargas = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/operacoes/fretes');
    cargas.value = res.data;
  } catch (error) {
    console.error('Erro ao carregar radar de fretes:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(carregarCargas);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>