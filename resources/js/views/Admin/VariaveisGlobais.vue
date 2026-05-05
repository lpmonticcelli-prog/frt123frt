<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Variáveis Globais do Sistema</h2>
          <p class="text-sm text-gray-400">Configurações core que afetam a lógica de negócio da 123fretei.</p>
        </div>
        <button 
          @click="salvarAlteracoes" 
          :disabled="isSaving"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded shadow transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ isSaving ? 'Processando...' : 'Salvar Alterações' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in">
      
      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm focus-within:ring-2 focus-within:ring-blue-500 transition-shadow">
        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Taxa da Plataforma (%)</label>
        <div class="flex items-center">
          <input 
            type="number" 
            step="0.01"
            v-model.number="config.taxa_plataforma" 
            class="w-full bg-white border border-gray-300 text-gray-900 text-2xl font-black rounded block p-2 outline-none focus:border-blue-500"
          >
        </div>
        <p class="mt-2 text-xs text-gray-400">Percentual retido por frete concluído.</p>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm focus-within:ring-2 focus-within:ring-blue-500 transition-shadow">
        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">SLA Aceite (Minutos)</label>
        <div class="flex items-center">
          <input 
            type="number" 
            v-model.number="config.tempo_limite_aceite_minutos" 
            class="w-full bg-white border border-gray-300 text-gray-900 text-2xl font-black rounded block p-2 outline-none focus:border-blue-500"
          >
        </div>
        <p class="mt-2 text-xs text-gray-400">Tempo limite para um motorista aceitar a carga.</p>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm focus-within:ring-2 focus-within:ring-blue-500 transition-shadow">
        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Ciclo de Faturamento (Dias)</label>
        <div class="flex items-center">
          <input 
            type="number" 
            v-model.number="config.dias_faturamento" 
            class="w-full bg-white border border-gray-300 text-gray-900 text-2xl font-black rounded block p-2 outline-none focus:border-blue-500"
          >
        </div>
        <p class="mt-2 text-xs text-gray-400">Janela de consolidação de boletos para indústrias.</p>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const config = ref({
  taxa_plataforma: null,
  tempo_limite_aceite_minutos: null,
  dias_faturamento: null
});

const loading = ref(true);
const isSaving = ref(false);

const carregarVariaveis = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/config/variaveis');
    config.value = {
      taxa_plataforma: parseFloat(res.data.taxa_plataforma),
      tempo_limite_aceite_minutos: parseInt(res.data.tempo_limite_aceite_minutos),
      dias_faturamento: parseInt(res.data.dias_faturamento)
    };
  } catch (error) {
    console.error('Erro ao carregar configurações:', error);
    alert('Falha ao conectar com a base de regras lógicas.');
  } finally {
    loading.value = false;
  }
};

const salvarAlteracoes = async () => {
  if (!confirm('Atenção: Alterar estas variáveis afeta os cálculos financeiros e operacionais de todo o sistema imediatamente. Confirma a alteração?')) return;
  
  isSaving.value = true;
  try {
    const res = await axios.put('/api/admin/config/variaveis', config.value);
    alert(res.data.message);
  } catch (error) {
    console.error('Erro ao salvar configurações:', error);
    const msg = error.response?.data?.message || 'Erro crítico ao gravar as variáveis.';
    alert(msg);
    carregarVariaveis(); // Recarrega do backend em caso de erro para manter a verdade factual na tela
  } finally {
    isSaving.value = false;
  }
};

onMounted(carregarVariaveis);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Oculta as setas nativas dos inputs type="number" para uma estética mais limpa */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>