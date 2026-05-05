<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Faturamento de Indústrias</h2>
          <p class="text-sm text-gray-400">Emissão de boletos e cobrança consolidada de embarcadores.</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 flex flex-col items-center justify-center animate-fade-in text-center">
      <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mb-6">
        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
      </div>
      
      <h3 class="text-2xl font-black text-gray-900 mb-2">Módulo Restrito (Fase 2)</h3>
      
      <p class="text-gray-500 max-w-md bg-gray-50 p-4 rounded border border-gray-200 font-mono text-sm">
        > {{ serverMessage }}
      </p>
      
      <div class="mt-8 space-y-2 text-sm text-gray-400 max-w-lg">
        <p>A arquitetura de faturamento (integração bancária, PIX automatizado e emissão de NFe) está agendada para a próxima fase do roadmap da 123fretei.</p>
        <p>A arrecadação de taxas atualmente é monitorizada em tempo real pela aba <strong>Extrato & Taxas</strong>.</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const serverMessage = ref('A carregar status da API...');

const verificarStatusModulo = async () => {
  try {
    const res = await axios.get('/api/admin/financeiro/faturamento');
    serverMessage.value = res.data.message || 'Módulo em Desenvolvimento.';
  } catch (error) {
    serverMessage.value = 'Erro ao consultar status do motor de faturamento.';
  }
};

onMounted(verificarStatusModulo);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>