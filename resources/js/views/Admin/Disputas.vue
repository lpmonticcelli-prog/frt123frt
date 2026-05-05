<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-red-200 bg-red-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Resolução de Disputas e Sinistros</h2>
          <p class="text-sm text-red-200">Painel de intervenção de emergência para cargas com problemas operacionais.</p>
        </div>
        <button @click="carregarDisputas" :disabled="loading" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded shadow transition-colors disabled:opacity-50">
          Atualizar Painel
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Data do Ocorrido</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Rota / Carga Afetada</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Envolvidos</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ação Bloqueada</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="disputas?.length === 0">
            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
              <span class="block text-4xl mb-2">✅</span>
              <span class="font-bold text-lg text-gray-800">Operação Limpa</span><br>
              Nenhuma disputa ou sinistro reportado na plataforma.
            </td>
          </tr>
          <tr v-for="carga in disputas" :key="carga.id" class="hover:bg-red-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ new Date(carga.updated_at).toLocaleDateString('pt-BR') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-gray-900">{{ carga.cidade_origem }} ➔ {{ carga.cidade_destino }}</div>
              <div class="text-xs text-gray-500">Protocolo: #{{ carga.id }} • {{ carga.produto }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-xs">
              <div class="text-red-800"><span class="font-bold">Indústria:</span> {{ carga.embarcador?.razao_social || 'N/A' }}</div>
              <div class="text-red-800 mt-1"><span class="font-bold">Motorista:</span> {{ carga.motorista?.user?.name || 'N/A' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <span class="px-2 py-1 text-xs font-bold rounded-md bg-red-100 text-red-800 uppercase animate-pulse">
                EM DISPUTA
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <button @click="abrirIntervencao(carga)" class="px-3 py-1.5 bg-gray-900 text-white font-bold rounded hover:bg-gray-700 text-xs shadow transition-colors">
                Intervir
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="cargaAtiva" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="fecharModal"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full border-t-4 border-red-600">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-xl font-black text-gray-900 mb-1">Mesa de Decisão: Carga #{{ cargaAtiva.id }}</h3>
            <p class="text-sm text-gray-500 mb-6">Aja com cautela. Decisões tomadas aqui são irreversíveis e afetam o financeiro.</p>
            
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="block text-xs font-bold text-gray-400 uppercase">Rota</span>
                  <span class="font-bold text-gray-800">{{ cargaAtiva.cidade_origem }} ➔ {{ cargaAtiva.cidade_destino }}</span>
                </div>
                <div>
                  <span class="block text-xs font-bold text-gray-400 uppercase">Valor do Frete</span>
                  <span class="font-bold text-blue-600">R$ {{ Number(cargaAtiva.valor_frete).toLocaleString('pt-BR', {minimumFractionDigits: 2}) }}</span>
                </div>
                <div class="col-span-2">
                  <span class="block text-xs font-bold text-gray-400 uppercase">Embarcador (Contratante)</span>
                  <span class="font-bold text-gray-800">{{ cargaAtiva.embarcador?.razao_social }}</span>
                </div>
                <div class="col-span-2">
                  <span class="block text-xs font-bold text-gray-400 uppercase">Motorista (Transportador)</span>
                  <span class="font-bold text-gray-800">{{ cargaAtiva.motorista?.user?.name }} (CPF: {{ cargaAtiva.motorista?.cpf }})</span>
                </div>
              </div>
            </div>

            <div class="space-y-3">
              <button @click="processarDecisao('finalizar')" :disabled="processando" class="w-full flex justify-between items-center px-4 py-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors disabled:opacity-50 text-left">
                <div>
                  <span class="block font-black text-green-800 text-sm">Forçar Finalização (A Favor do Motorista)</span>
                  <span class="block text-xs text-green-600 mt-1">Libera o pagamento para o motorista e encerra o ciclo.</span>
                </div>
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </button>

              <button @click="processarDecisao('cancelar')" :disabled="processando" class="w-full flex justify-between items-center px-4 py-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors disabled:opacity-50 text-left">
                <div>
                  <span class="block font-black text-red-800 text-sm">Cancelar Frete (A Favor do Embarcador)</span>
                  <span class="block text-xs text-red-600 mt-1">Cancela o contrato, nenhuma taxa será cobrada.</span>
                </div>
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </button>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end border-t border-gray-200">
            <button @click="fecharModal" :disabled="processando" class="px-4 py-2 bg-white text-gray-700 font-bold text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
              Voltar
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const disputas = ref([]);
const loading = ref(true);
const processando = ref(false);
const cargaAtiva = ref(null);

const carregarDisputas = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/operacoes/disputas');
    disputas.value = res.data;
  } catch (error) {
    console.error('Erro ao carregar disputas:', error);
  } finally {
    loading.value = false;
  }
};

const abrirIntervencao = (carga) => {
  cargaAtiva.value = carga;
};

const fecharModal = () => {
  if (processando.value) return;
  cargaAtiva.value = null;
};

const processarDecisao = async (acao) => {
  if (!confirm(`Tem certeza que deseja ${acao === 'finalizar' ? 'FORÇAR A FINALIZAÇÃO' : 'CANCELAR'} esta carga? Esta ação é irreversível.`)) return;
  
  processando.value = true;
  try {
    const res = await axios.post(`/api/admin/operacoes/disputas/${cargaAtiva.value.id}/resolver`, { acao });
    alert(res.data.message);
    fecharModal();
    await carregarDisputas();
  } catch (error) {
    alert(error.response?.data?.message || 'Erro ao processar a resolução da disputa.');
  } finally {
    processando.value = false;
  }
};

onMounted(carregarDisputas);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>