<template>
  <div class="space-y-6 relative">
    
    <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Painel de Controle Logístico</h2>
        <p class="text-sm text-gray-500 mt-1">Gira as suas cargas publicadas, em trânsito e em auditoria.</p>
      </div>
      <div class="flex gap-3">
        <button @click="fetchCargas(1)" :disabled="loading" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50 flex items-center shadow-sm">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          {{ loading ? 'A Sincronizar...' : 'Atualizar Painel' }}
        </button>
        <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="px-5 py-2 bg-slate-900 text-white rounded-lg text-sm font-bold shadow-sm hover:bg-slate-800 transition-colors flex items-center">
          + Publicar Novo Frete
        </router-link>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      
      <div v-if="loading && cargas.length === 0" class="p-12 text-center text-gray-500 font-medium text-sm">
        A carregar histórico de operações...
      </div>

      <div v-else-if="!cargas || cargas.length === 0" class="p-16 text-center">
        <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
        </div>
        <h3 class="text-base font-bold text-slate-900">Nenhuma carga encontrada</h3>
        <p class="text-sm text-gray-500 mt-1">Você ainda não possui publicações nesta página.</p>
        <div class="mt-6">
          <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 transition-colors">
            Publicar primeira carga
          </router-link>
        </div>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-left">
            <thead class="bg-slate-50">
              <tr>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Rota</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Produto</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Veículo</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Valor Oferta</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Motorista</th>
                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100" :class="{ 'opacity-50 pointer-events-none': loading }">
              <tr v-for="carga in cargas" :key="carga.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-bold text-slate-900">{{ carga.cidade_origem }}/{{ carga.uf_origem }}</div>
                  <div class="text-sm text-gray-500">para {{ carga.cidade_destino }}/{{ carga.uf_destino }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900">{{ carga.produto }}</div>
                  <div class="text-xs text-gray-500 mt-0.5">{{ carga.peso_kg }} kg</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900 capitalize">{{ carga.tipo_veiculo?.replace('_', ' ') }}</div>
                  <div class="text-xs text-gray-500 mt-0.5 capitalize">{{ carga.tipo_carroceria?.replace('_', ' ') }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-slate-900">
                  R$ {{ carga.valor_frete }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="['px-3 py-1 inline-flex text-[10px] font-black uppercase tracking-wider rounded-md border', getStatusClass(carga.status)]">
                    {{ carga.status.replace('_', ' ') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap border-l border-gray-100 bg-slate-50/30">
                  <div v-if="carga.motorista_id && carga.motorista">
                    <div class="text-sm font-bold text-slate-900 flex items-center">
                      {{ carga.motorista.user?.name || 'ID: ' + carga.motorista_id }}
                      <span v-if="carga.aceite_log" title="Contrato Assinado" class="ml-1 text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                      </span>
                    </div>
                    <div class="text-xs font-medium text-orange-600 mb-2">{{ carga.motorista.user?.phone || 'Contato Oculto' }}</div>
                  </div>
                  <div v-else class="text-xs text-gray-400 italic font-medium">
                    Aguardando Motorista
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right space-y-2 flex flex-col items-end">
                  <div class="space-x-3">
                    <template v-if="carga.status === 'publicada'">
                      <router-link :to="{ name: 'EmbarcadorEditarCarga', params: { id: carga.id } }" class="text-blue-600 hover:text-blue-800 font-bold transition-colors text-sm">Editar</router-link>
                      <button @click="cancelarCarga(carga.id)" class="text-red-600 hover:text-red-800 font-bold transition-colors text-sm">Cancelar</button>
                    </template>
                    
                    <template v-else-if="carga.status === 'em_auditoria'">
                      <button @click="abrirModalPod(carga)" class="inline-flex items-center px-4 py-1.5 bg-yellow-400 border border-yellow-500 text-yellow-900 font-black text-xs rounded hover:bg-yellow-500 transition-colors shadow-sm animate-pulse">
                        ⚖️ Auditar Entrega
                      </button>
                    </template>

                    <template v-else-if="carga.status === 'entregue' || carga.status === 'concluida'">
                      <button @click="abrirModalPod(carga)" class="inline-flex items-center px-3 py-1.5 bg-green-50 border border-green-200 text-green-700 font-bold text-xs rounded hover:bg-green-100 transition-colors">
                        Ver Comprovantes
                      </button>
                    </template>
                    
                    <template v-else-if="carga.status === 'em_transito'">
                      <router-link :to="{ name: 'EmbarcadorRastreamento', params: { id: carga.id } }" class="inline-flex items-center px-3 py-1.5 bg-slate-900 text-white text-xs font-bold rounded shadow-sm hover:bg-slate-800 transition-colors">
                        📍 Acompanhar Rota
                      </router-link>
                    </template>
                    
                    <template v-else>
                      <span class="text-gray-400 text-[10px] font-black uppercase tracking-wider">Em operação</span>
                    </template>
                  </div>

                  <button v-if="carga.publicacao_log" @click="abrirModalContrato(carga, 'embarcador')" class="inline-flex items-center px-2 py-1 bg-slate-100 text-slate-700 border border-slate-200 font-bold text-[10px] rounded hover:bg-slate-200 transition-colors shadow-sm mt-2">
                    📄 Meu Certificado
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pagination.last_page > 1" class="px-6 py-4 bg-white border-t border-gray-200 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Página <span class="font-bold">{{ pagination.current_page }}</span> de <span class="font-bold">{{ pagination.last_page }}</span>
          </div>
          <div class="space-x-2">
            <button @click="fetchCargas(pagination.current_page - 1)" :disabled="pagination.current_page === 1 || loading" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">
              Anterior
            </button>
            <button @click="fetchCargas(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page || loading" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">
              Próxima
            </button>
          </div>
        </div>
      </template>
    </div>

    <!-- Modal de Auditoria (PoD) -->
    <div v-if="showModalPod" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-pod-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="fecharModalPod"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full border border-gray-200">
          <div class="bg-white px-6 pt-5 pb-4 sm:p-8 sm:pb-6">
            <div class="sm:flex sm:items-start w-full">
              <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                  <h3 class="text-xl font-bold text-slate-900" id="modal-pod-title">
                    Auditoria de Entrega - {{ cargaSelecionada?.cidade_destino }}/{{ cargaSelecionada?.uf_destino }}
                  </h3>
                  <span :class="getStatusClass(cargaSelecionada?.status)" class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border">
                    {{ cargaSelecionada?.status?.replace('_', ' ') }}
                  </span>
                </div>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div class="flex flex-col items-center bg-slate-50 p-6 rounded-xl border border-slate-200">
                    <span class="font-black text-slate-700 uppercase text-xs tracking-wider mb-3">Canhoto Assinado</span>
                    <a v-if="cargaSelecionada?.foto_canhoto" :href="getImageUrl(cargaSelecionada.foto_canhoto)" target="_blank" class="block w-full">
                      <img :src="getImageUrl(cargaSelecionada.foto_canhoto)" alt="Foto do Canhoto" class="w-full h-72 object-contain rounded-lg border border-slate-300 hover:opacity-90 transition-opacity cursor-pointer bg-white shadow-sm" />
                    </a>
                    <div v-else class="h-72 flex items-center justify-center text-gray-400 italic text-sm">Arquivo não encontrado</div>
                  </div>
                  <div class="flex flex-col items-center bg-slate-50 p-6 rounded-xl border border-slate-200">
                    <span class="font-black text-slate-700 uppercase text-xs tracking-wider mb-3">Carga no Destino (GPS)</span>
                    <div v-if="cargaSelecionada?.latitude_entrega" class="w-full text-xs font-mono text-center text-blue-600 mb-2">
                      LAT: {{ cargaSelecionada.latitude_entrega }} | LNG: {{ cargaSelecionada.longitude_entrega }}
                    </div>
                    <a v-if="cargaSelecionada?.foto_carga" :href="getImageUrl(cargaSelecionada.foto_carga)" target="_blank" class="block w-full">
                      <img :src="getImageUrl(cargaSelecionada.foto_carga)" alt="Foto da Carga" class="w-full h-64 object-contain rounded-lg border border-slate-300 hover:opacity-90 transition-opacity cursor-pointer bg-white shadow-sm" />
                    </a>
                    <div v-else class="h-64 flex items-center justify-center text-gray-400 italic text-sm">Arquivo não encontrado</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-slate-50 px-6 py-4 flex items-center justify-between border-t border-slate-200">
            <div class="text-xs text-gray-500 font-medium">
              A liquidação do CIOT é acionada automaticamente após a aprovação.
            </div>
            <div class="flex gap-3">
              <template v-if="cargaSelecionada?.status === 'em_auditoria'">
                <button type="button" @click="abrirDisputa" :disabled="actionLoading" class="inline-flex justify-center rounded-lg border border-red-300 shadow-sm px-5 py-2.5 bg-red-50 text-sm font-bold text-red-700 hover:bg-red-100 focus:outline-none transition-colors disabled:opacity-50">
                  ⚠️ Reprovar / Disputa
                </button>
                <button type="button" @click="aprovarPagamento" :disabled="actionLoading" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-green-600 text-sm font-bold text-white hover:bg-green-700 focus:outline-none transition-colors disabled:opacity-50">
                  ✅ Aprovar & Pagar
                </button>
              </template>
              <button type="button" @click="fecharModalPod" :disabled="actionLoading" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-gray-50 focus:outline-none transition-colors">
                Fechar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Contrato Eletrônico -->
    <!-- (Mantido igual, oculto para concisão visual no bloco) -->
    
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const cargas = ref([]);
const loading = ref(true);
const actionLoading = ref(false);

const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

const showModalPod = ref(false);
const showModalContrato = ref(false);
const cargaSelecionada = ref(null); 
const tipoCertificadoSelecionado = ref('motorista');

const getStatusClass = (status) => {
  const classes = {
    publicada: 'bg-blue-50 text-blue-700 border-blue-200',
    alocada: 'bg-amber-50 text-amber-700 border-amber-200',
    em_transito: 'bg-purple-50 text-purple-700 border-purple-200',
    em_auditoria: 'bg-yellow-100 text-yellow-800 border-yellow-300', // Status Novo S3
    entregue: 'bg-green-50 text-green-700 border-green-200',
    concluida: 'bg-green-50 text-green-700 border-green-200',
    cancelada: 'bg-red-50 text-red-700 border-red-200',
    em_disputa: 'bg-red-100 text-red-800 border-red-300'
  };
  return classes[status] || 'bg-gray-50 text-gray-700 border-gray-200';
};

// Zero Trust na visualização de Arquivos da Nuvem (S3 / DO Spaces)
const getImageUrl = (path) => {
  if (!path) return '';
  // Se já for uma URL completa do S3 assinada, renderiza direto.
  if (path.startsWith('http')) return path;
  
  // Se for apenas o PATH, aponta para a rota assinada do seu Backend ou direto pro bucket
  // (O ideal é sua API devolver as presigned_urls no load da carga)
  return `${import.meta.env.VITE_STORAGE_URL || ''}/storage/${path}`;
};

const fetchCargas = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/cargas?page=${page}`);
    if (response.data && response.data.data) {
      cargas.value = response.data.data;
      pagination.value = { current_page: response.data.current_page, last_page: response.data.last_page, total: response.data.total };
    } else {
      cargas.value = response.data || [];
    }
  } catch (error) {
    console.error('Erro ao carregar o mural:', error);
  } finally { loading.value = false; }
};

const cancelarCarga = async (id) => {
  if (!confirm('Tem certeza que deseja cancelar esta carga?')) return;
  try {
    await axios.delete(`/api/cargas/${id}`);
    fetchCargas(pagination.value.current_page);
  } catch (error) { alert('Erro ao tentar cancelar a carga.'); }
};

const abrirModalPod = (carga) => { cargaSelecionada.value = carga; showModalPod.value = true; };
const fecharModalPod = () => { showModalPod.value = false; if (!showModalContrato.value) cargaSelecionada.value = null; };

// ==========================================
// MESA FINANCEIRA: APROVAÇÃO & DISPUTA
// ==========================================
const aprovarPagamento = async () => {
  if (!confirm('ATENÇÃO: Ao aprovar a entrega, o frete será enviado para liquidação e o CIOT será finalizado. Confirma?')) return;
  
  actionLoading.value = true;
  try {
    await axios.post(`/api/v1/embarcador/cargas/${cargaSelecionada.value.id}/aprovar`);
    alert('✅ Sucesso! Pagamento aprovado. A carga agora será liquidada na Pamcard/NDD.');
    fecharModalPod();
    fetchCargas(pagination.value.current_page);
  } catch (error) {
    alert(error.response?.data?.message || 'Erro ao aprovar o pagamento.');
  } finally {
    actionLoading.value = false;
  }
};

const abrirDisputa = async () => {
  const motivo = prompt('Descreva o motivo da reprovação (Ex: Canhoto rasurado, Carga Avariada):');
  if (!motivo) return; // Cancela se o campo ficar vazio

  actionLoading.value = true;
  try {
    await axios.post(`/api/v1/embarcador/cargas/${cargaSelecionada.value.id}/disputa`, { motivo });
    alert('⚠️ Carga bloqueada. Uma disputa foi aberta na Mesa de Operações (SAC).');
    fecharModalPod();
    fetchCargas(pagination.value.current_page);
  } catch (error) {
    alert(error.response?.data?.message || 'Erro ao abrir disputa.');
  } finally {
    actionLoading.value = false;
  }
};

onMounted(() => fetchCargas());
</script>
