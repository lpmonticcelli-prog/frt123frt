<template>
  <div class="space-y-4 sm:space-y-6 max-w-4xl mx-auto pb-safe-bottom">
    
    <!-- HEADER FINANCEIRO (SALDO) -->
    <div class="bg-surface-900 rounded-xl shadow-clinical-lg p-6 sm:p-8 text-white relative overflow-hidden border border-surface-800">
      <!-- Elemento Decorativo (Glow) -->
      <div class="absolute top-0 right-0 -mr-8 -mt-8 w-48 h-48 rounded-full bg-brand-500 opacity-20 blur-3xl pointer-events-none" aria-hidden="true"></div>
      
      <div class="relative z-10">
        <h2 class="text-[11px] font-black text-surface-400 uppercase tracking-widest mb-1 flex items-center">
          <svg class="w-4 h-4 mr-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          Saldo Disponível (PIX)
        </h2>
        
        <div v-if="loading" class="animate-pulse h-12 sm:h-14 w-48 bg-surface-800 rounded mt-2"></div>
        <div v-else class="text-4xl sm:text-5xl font-black tracking-tight text-emerald-400 tabular-nums">
          {{ formatMoney(saldo) }}
        </div>
        
        <div class="mt-6 sm:mt-8 flex gap-3">
          <button class="w-full sm:w-auto bg-brand-600 hover:bg-brand-500 text-white font-bold px-6 py-3.5 sm:py-3 rounded-md shadow-clinical-md transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-surface-900 flex items-center justify-center text-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            Solicitar Saque
          </button>
        </div>
      </div>
    </div>

    <!-- EXTRATO DE TRANSAÇÕES -->
    <div class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden">
      <div class="px-4 py-4 sm:px-6 border-b border-surface-200 bg-surface-50 flex justify-between items-center">
        <h3 class="text-base sm:text-lg font-bold text-surface-900 tracking-tight">Histórico de Transações</h3>
      </div>

      <div v-if="loading" class="p-12 flex flex-col items-center justify-center text-surface-500 font-bold space-y-3">
        <svg class="w-8 h-8 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span>A carregar livro-razão...</span>
      </div>
      
      <div v-else-if="!transacoes || transacoes.length === 0" class="p-12 text-center flex flex-col items-center">
        <div class="w-12 h-12 bg-surface-100 rounded-full flex items-center justify-center mb-3">
          <svg class="w-6 h-6 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <p class="text-surface-900 font-bold text-sm">Nenhuma movimentação financeira encontrada.</p>
        <p class="text-surface-500 text-xs mt-1">Seus ganhos e saques aparecerão aqui.</p>
      </div>

      <div v-else class="divide-y divide-surface-100">
        <div v-for="t in transacoes" :key="t.id" class="px-4 py-4 sm:px-6 sm:py-5 flex items-center justify-between hover:bg-surface-50 transition-colors group">
          <div class="flex items-center gap-3 sm:gap-4 min-w-0">
            <div :class="t.tipo === 'credito' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100'" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-black border shrink-0 shadow-clinical-sm transition-transform group-hover:scale-105">
              <svg v-if="t.tipo === 'credito'" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
              <svg v-else class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
            </div>
            <div class="min-w-0">
              <p class="text-sm font-bold text-surface-900 truncate leading-tight" :title="t.descricao">{{ t.descricao }}</p>
              <p class="text-[11px] text-surface-500 font-medium mt-0.5">{{ new Date(t.created_at).toLocaleString('pt-BR') }}</p>
            </div>
          </div>
          <div :class="t.tipo === 'credito' ? 'text-emerald-600' : 'text-rose-600'" class="text-sm sm:text-base font-black text-right tabular-nums whitespace-nowrap pl-3">
            {{ t.tipo === 'credito' ? '+' : '-' }} {{ formatMoney(t.valor) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const saldo = ref(0);
const transacoes = ref([]);
const loading = ref(true);

const formatMoney = (value) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const carregarExtrato = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/motorista/carteira/extrato');
    
    saldo.value = response.data?.saldo_disponivel || 0;
    transacoes.value = response.data?.transacoes || [];
    
  } catch (error) {
    console.error('Erro ao carregar carteira', error);
    saldo.value = 0;
    transacoes.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(carregarExtrato);
</script>