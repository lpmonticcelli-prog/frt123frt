<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-200 bg-slate-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Radar de Faturamento & Inadimplência</h2>
          <p class="text-sm text-slate-400">Controle de recebíveis, taxas SaaS e bloqueio de Embarcadores.</p>
        </div>
        <button @click="fetchMetrics" :disabled="loading" class="px-4 py-2 border border-slate-700 rounded-lg text-sm font-bold text-white bg-slate-800 hover:bg-slate-700 transition-colors disabled:opacity-50 flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          Sincronizar Motor
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
        <span class="text-xs font-black text-slate-400 uppercase tracking-wider mb-1">Receita Prevista (Mês)</span>
        <span class="text-2xl font-black text-slate-900">{{ formatarMoeda(metrics.receita_prevista) }}</span>
      </div>
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
        <span class="text-xs font-black text-slate-400 uppercase tracking-wider mb-1">Receita Realizada (Paga)</span>
        <span class="text-2xl font-black text-green-600">{{ formatarMoeda(metrics.receita_realizada) }}</span>
      </div>
      <div class="bg-red-50 p-5 rounded-xl border border-red-200 shadow-sm flex flex-col justify-center">
        <span class="text-xs font-black text-red-400 uppercase tracking-wider mb-1">Risco Absoluto (Inadimplência)</span>
        <span class="text-2xl font-black text-red-600">{{ formatarMoeda(metrics.total_inadimplencia) }}</span>
      </div>
      <div class="bg-slate-900 p-5 rounded-xl border border-slate-800 shadow-sm flex flex-col justify-center">
        <span class="text-xs font-black text-slate-400 uppercase tracking-wider mb-1">Contas Congeladas</span>
        <span class="text-2xl font-black text-white">{{ metrics.contas_congeladas }}</span>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Radar de Embarcadores com Débito</h3>
      </div>
      
      <div v-if="loading" class="p-12 text-center text-slate-500 font-medium text-sm">
        Sincronizando com o pool de banco de dados...
      </div>
      
      <table v-else class="min-w-full divide-y divide-slate-200 text-left">
        <thead class="bg-white">
          <tr>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Embarcador</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Referência</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Valor</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Vencimento</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Ações Críticas</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-100">
          <tr v-for="fatura in faturasComRisco" :key="fatura.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-slate-900">{{ fatura.embarcador.razao_social }}</div>
              <div class="text-xs text-slate-500 font-mono">{{ fatura.embarcador.cnpj }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700 font-mono">
              {{ fatura.mes_referencia }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-slate-900">
              {{ formatarMoeda(fatura.valor_total) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div :class="['text-sm font-bold', isVencida(fatura) ? 'text-red-600' : 'text-slate-700']">
                {{ formatarData(fatura.data_vencimento) }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="['px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-md border', getStatusClass(fatura)]">
                {{ isVencida(fatura) ? 'VENCIDA' : fatura.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <button 
                @click="congelarEmbarcador(fatura.embarcador_id)" 
                :disabled="fatura.embarcador.status === 'congelado' || processandoAcao"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-bold rounded shadow-sm hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ fatura.embarcador.status === 'congelado' ? 'Já Congelado' : 'Congelar Conta' }}
              </button>
            </td>
          </tr>
          <tr v-if="faturasComRisco.length === 0">
             <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-medium">Nenhum risco de inadimplência detectado na malha financeira.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(true);
const processandoAcao = ref(false);

const metrics = ref({
  receita_prevista: 0,
  receita_realizada: 0,
  total_inadimplencia: 0,
  contas_congeladas: 0
});

const faturasComRisco = ref([]);

const formatarMoeda = (valor) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);
};

const formatarData = (dataString) => {
  if (!dataString) return '--';
  return new Date(dataString).toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

const isVencida = (fatura) => {
  if (fatura.status === 'paga') return false;
  return new Date(fatura.data_vencimento) < new Date();
};

const getStatusClass = (fatura) => {
  if (isVencida(fatura)) return 'bg-red-50 text-red-700 border-red-200';
  if (fatura.status === 'pendente') return 'bg-amber-50 text-amber-700 border-amber-200';
  return 'bg-slate-100 text-slate-500 border-slate-200';
};

const fetchMetrics = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/faturamento/radar');
    metrics.value = response.data.metrics;
    faturasComRisco.value = response.data.faturas_risco;
  } catch (error) {
    console.error('Falha de I/O na API Admin Faturamento', error);
  } finally {
    loading.value = false;
  }
};

const congelarEmbarcador = async (embarcadorId) => {
  if(!confirm('OPERAÇÃO CRÍTICA: Bloquear a conta deste embarcador irá paralisar a postagem de novos fretes imediatamente. Proceder?')) return;
  
  processandoAcao.value = true;
  try {
    await axios.post(`/api/v1/admin/embarcadores/${embarcadorId}/congelar`);
    alert('✅ Intervenção Executada: Conta do Embarcador suspensa por quebra contratual (Inadimplência).');
    await fetchMetrics();
  } catch (error) {
    alert('Erro ao aplicar lock no banco de dados para congelamento.');
  } finally {
    processandoAcao.value = false;
  }
};

onMounted(fetchMetrics);
</script>