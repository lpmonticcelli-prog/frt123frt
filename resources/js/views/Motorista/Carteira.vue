<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    
    <div class="bg-slate-900 rounded-2xl shadow-lg p-8 text-white relative overflow-hidden">
      <div class="absolute top-0 right-0 -mr-8 -mt-8 w-48 h-48 rounded-full bg-slate-800 opacity-50 blur-2xl pointer-events-none"></div>
      
      <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1">Saldo Disponível</h2>
      <div v-if="loading" class="animate-pulse h-12 w-48 bg-slate-800 rounded mt-2"></div>
      <div v-else class="text-5xl font-black tracking-tight text-green-400">
        {{ formatMoney(saldo) }}
      </div>
      
      <div class="mt-8 flex gap-4">
        <button class="bg-green-500 hover:bg-green-400 text-slate-900 font-black px-6 py-3 rounded-lg shadow-md transition-colors">
          💸 Solicitar Saque (Pix)
        </button>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
        <h3 class="text-sm font-black text-slate-700 uppercase tracking-wider">Histórico de Transações</h3>
      </div>

      <div v-if="loading" class="p-12 text-center text-slate-500 font-bold">A carregar livro-razão...</div>
      
      <div v-else-if="!transacoes || transacoes.length === 0" class="p-12 text-center">
        <p class="text-slate-500 font-bold">Nenhuma movimentação financeira encontrada.</p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div v-for="t in transacoes" :key="t.id" class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
          <div class="flex items-center gap-4">
            <div :class="t.tipo === 'credito' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-black">
              {{ t.tipo === 'credito' ? '↓' : '↑' }}
            </div>
            <div>
              <p class="text-sm font-bold text-slate-900">{{ t.descricao }}</p>
              <p class="text-xs text-slate-500">{{ new Date(t.created_at).toLocaleString('pt-BR') }}</p>
            </div>
          </div>
          <div :class="t.tipo === 'credito' ? 'text-green-600' : 'text-red-600'" class="text-base font-black text-right">
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
    
    // CORREÇÃO CIRÚRGICA: Garantia de tipagem (Fallback para 0 e Array vazio)
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