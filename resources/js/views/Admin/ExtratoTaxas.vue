<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Extrato de Taxas (Fee Global: {{ currentFee }}%)</h2>
          <p class="text-sm text-gray-400">Consolidação de receitas geradas por fretes concluídos com sucesso.</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in" v-if="!loading">
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Volume Transacionado (Concluído)</h3>
        <p class="text-4xl font-black mt-2 text-gray-900">{{ formatMoney(totalTransacionado) }}</p>
      </div>
      <div class="bg-gradient-to-br from-green-900 to-green-700 rounded-xl p-6 shadow-lg border border-green-600 text-white">
        <h3 class="text-green-200 text-sm font-bold uppercase tracking-wider">Receita Líquida (Caixa Plataforma)</h3>
        <p class="text-4xl font-black mt-2 text-white">{{ formatMoney(totalReceita) }}</p>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-10">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Data de Registro</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Rota Concluída</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Valor Frete</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Taxa do Contrato</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-green-600 uppercase">Receita Retida</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="extratos?.length === 0">
            <td colspan="6" class="px-6 py-10 text-center text-gray-500 font-bold">Nenhum frete concluído para gerar extrato.</td>
          </tr>
          <tr v-for="extrato in extratos" :key="extrato.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ new Date(extrato.created_at).toLocaleDateString('pt-BR') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-gray-900">{{ extrato.cidade_origem }} ➔ {{ extrato.cidade_destino }}</div>
              <div class="text-xs text-gray-400">ID da Carga: #{{ extrato.id }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs font-bold rounded-md bg-green-100 text-green-800 uppercase">
                {{ extrato.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <div class="text-sm text-gray-600">{{ formatMoney(extrato.valor_frete) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">
                {{ extrato.percentual_aplicado }}%
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <div class="text-sm font-black text-green-600">+ {{ formatMoney(extrato.taxa_retida) }}</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const extratos = ref([]);
const currentFee = ref(0); // Inicia zerado até a API responder
const loading = ref(true);

const formatMoney = (value) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const totalTransacionado = computed(() => {
  return extratos.value?.reduce((acc, curr) => acc + Number(curr.valor_frete), 0);
});

const totalReceita = computed(() => {
  return extratos.value?.reduce((acc, curr) => acc + Number(curr.taxa_retida), 0);
});

const carregarExtrato = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/admin/financeiro/extrato');
    // Mapeamento correto baseando-se na nova estrutura JSON do Controller
    extratos.value = res.data.dados;
    currentFee.value = res.data.configuracao_atual;
  } catch (error) {
    console.error('Erro ao carregar extrato financeiro:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(carregarExtrato);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>