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
      
      <div v-if="loading && cargas?.length === 0" class="p-12 text-center text-gray-500 font-medium text-sm">
        A carregar histórico de operações...
      </div>

      <div v-else-if="!cargas || cargas?.length === 0" class="p-16 text-center">
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
                    {{ carga.status?.replace('_', ' ') }}
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

                    <template v-else-if="carga.status === 'entregue' || carga.status === 'finalizada' || carga.status === 'concluida'">
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

    <div v-if="showModalContrato" class="fixed inset-0 z-50 overflow-y-auto print:static print:z-auto print:inset-auto" aria-labelledby="modal-contrato-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0 print:block print:p-0 print:min-h-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity print:hidden" @click="fecharModalContrato"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen print:hidden" aria-hidden="true">&#8203;</span>
        
        <div id="print-area" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full print:shadow-none print:w-full print:max-w-full print:rounded-none border border-gray-200">
          <div class="bg-white px-6 pt-6 pb-6 print:p-0">
            
            <div class="hidden print:block mb-8 border-b-4 border-gray-900 pb-4">
              <h1 class="text-2xl font-black text-gray-900 uppercase">123Fretei - Documento de Auditoria Logística</h1>
              <p class="text-sm text-gray-600 mt-1">Validação Criptográfica e Pagamento Eletrônico de Frete (PEF)</p>
            </div>

            <h3 class="text-lg leading-6 font-bold text-gray-900 border-b pb-2 mb-4 print:text-xl print:mt-4" id="modal-contrato-title">
              Certificado de Publicação (Embarcador)
            </h3>
            
            <div class="space-y-6">
              <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 print:bg-white print:border-gray-400 print:border">
                <p class="text-sm text-yellow-800 font-medium print:text-gray-800">
                  Este certificado possui validade jurídica e serve como prova imutável da oferta de carga e condições comerciais estabelecidas pelo Embarcador.
                </p>
              </div>

              <div class="bg-gray-900 p-4 rounded-md text-xs font-mono text-green-400 space-y-2 break-all shadow-inner print:bg-white print:text-black print:border print:border-gray-300">
                <p><span class="text-gray-400 font-bold print:text-gray-600">ID DA CARGA:</span> {{ cargaSelecionada?.id }}</p>
                <p><span class="text-gray-400 font-bold print:text-gray-600">DATA/HORA DO EVENTO (UTC):</span> {{ getLogSelecionado()?.data_evento }}</p>
                <p><span class="text-gray-400 font-bold print:text-gray-600">ENDEREÇO IP DO ASSINANTE:</span> {{ getLogSelecionado()?.ip_address }}</p>
                <p><span class="text-gray-400 font-bold print:text-gray-600">DISPOSITIVO:</span> {{ getLogSelecionado()?.user_agent }}</p>
                <p class="mt-3 pt-3 border-t border-gray-700 print:border-gray-300">
                  <span class="text-gray-400 font-bold block mb-1 print:text-gray-600">CHAVE CRIPTOGRÁFICA (SHA-256):</span> 
                  {{ getLogSelecionado()?.termo_hash }}
                </p>
              </div>

              <div class="text-xs text-gray-600 text-justify p-4 bg-gray-50 border border-gray-200 rounded-md print:bg-white print:border-gray-400">
                <strong class="block mb-2 text-gray-800">TERMO ASSINADO:</strong> 
                {{ extrairTextoOriginal() }}
              </div>

              <div v-if="cargaSelecionada?.ciot" class="border-t-2 border-dashed border-gray-300 pt-6 mt-6 print:break-inside-avoid">
                <h3 class="text-lg leading-6 font-black text-gray-900 mb-4 uppercase">Espelho de Pagamento de Frete (CIOT)</h3>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                  <div class="p-3 bg-blue-50 border border-blue-200 rounded print:bg-white print:border-gray-400">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase">Código CIOT (ANTT)</span>
                    <strong class="text-lg text-blue-900">{{ cargaSelecionada.ciot.codigo_ciot || 'Em Processamento' }}</strong>
                  </div>
                  <div class="p-3 bg-gray-50 border border-gray-200 rounded print:bg-white print:border-gray-400">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase">Status Operacional</span>
                    <strong class="text-lg text-gray-900 uppercase">{{ cargaSelecionada.ciot.status }}</strong>
                  </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded overflow-hidden print:border-gray-400 text-sm">
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                      <td class="px-4 py-2 font-bold text-gray-700 bg-gray-50 print:bg-white">Valor Bruto do Frete</td>
                      <td class="px-4 py-2 text-right font-mono">{{ formatMoney(cargaSelecionada.ciot.valor_frete_bruto) }}</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-2 font-bold text-gray-700 bg-gray-50 print:bg-white">(-) Retenção INSS</td>
                      <td class="px-4 py-2 text-right text-red-600 font-mono">{{ formatMoney(cargaSelecionada.ciot.imposto_inss) }}</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-2 font-bold text-gray-700 bg-gray-50 print:bg-white">(-) Retenção SEST/SENAT</td>
                      <td class="px-4 py-2 text-right text-red-600 font-mono">{{ formatMoney(cargaSelecionada.ciot.imposto_sest_senat) }}</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-2 font-bold text-gray-700 bg-gray-50 print:bg-white">(-) Retenção IRRF</td>
                      <td class="px-4 py-2 text-right text-red-600 font-mono">{{ formatMoney(cargaSelecionada.ciot.imposto_irrf) }}</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-2 font-bold text-gray-700 bg-gray-50 print:bg-white">(-) Taxa Operacional 123fretei</td>
                      <td class="px-4 py-2 text-right text-red-600 font-mono">{{ formatMoney(cargaSelecionada.ciot.taxa_123fretei) }}</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-2 font-bold text-gray-700 bg-blue-50 print:bg-white">Vale Pedágio Obrigatório</td>
                      <td class="px-4 py-2 text-right text-blue-600 font-mono">{{ formatMoney(cargaSelecionada.ciot.valor_vale_pedagio) }}</td>
                    </tr>
                    <tr class="bg-green-50 print:bg-white border-t-2 border-green-200 print:border-gray-800">
                      <td class="px-4 py-3 font-black text-green-900 uppercase">VALOR LÍQUIDO A RECEBER (MOTORISTA)</td>
                      <td class="px-4 py-3 text-right font-black text-green-700 text-lg font-mono">{{ formatMoney(cargaSelecionada.ciot.valor_frete_liquido) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 print:hidden">
            <button type="button" @click="fecharModalContrato" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
              Fechar
            </button>
            <button type="button" @click="imprimirCertificado" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
              🖨️ Imprimir PDF (CIOT)
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showModalPod" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-pod-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="fecharModalPod"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full border border-gray-200">
          <div class="bg-slate-50 px-6 py-4 flex items-center justify-between border-t border-slate-200">
            <div class="text-xs text-gray-500 font-medium">A liquidação do CIOT é acionada automaticamente após a aprovação.</div>
            <div class="flex gap-3">
              <template v-if="cargaSelecionada?.status === 'em_auditoria'">
                <button type="button" @click="abrirDisputa" class="bg-red-50 text-red-700 border border-red-300 px-5 py-2.5 rounded-lg font-bold text-sm">⚠️ Reprovar / Disputa</button>
                <button type="button" @click="aprovarPagamento" class="bg-green-600 text-white px-5 py-2.5 rounded-lg font-bold text-sm">✅ Aprovar & Pagar</button>
              </template>
              <button type="button" @click="fecharModalPod" class="bg-white text-slate-700 border border-gray-300 px-5 py-2.5 rounded-lg font-bold text-sm">Fechar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth'; 

const auth = useAuthStore();
const cargas = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

// Lógica de Modais
const showModalPod = ref(false);
const showModalContrato = ref(false); 
const cargaSelecionada = ref(null); 
const tipoCertificadoSelecionado = ref('embarcador');

const getStatusClass = (status) => {
  const classes = {
    publicada: 'bg-blue-50 text-blue-700 border-blue-200',
    alocada: 'bg-amber-50 text-amber-700 border-amber-200',
    em_transito: 'bg-purple-50 text-purple-700 border-purple-200',
    em_auditoria: 'bg-yellow-100 text-yellow-800 border-yellow-300',
    entregue: 'bg-green-50 text-green-700 border-green-200',
    finalizada: 'bg-green-50 text-green-700 border-green-200',
    cancelada: 'bg-red-50 text-red-700 border-red-200',
    em_disputa: 'bg-red-100 text-red-800 border-red-300'
  };
  return classes[status] || 'bg-gray-50 text-gray-700 border-gray-200';
};

// --- FUNÇÕES DE AUDITORIA E CIOT INJETADAS ---
const formatMoney = (value) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const abrirModalContrato = (carga, tipo) => {
  cargaSelecionada.value = carga;
  tipoCertificadoSelecionado.value = tipo;
  showModalContrato.value = true;
};

const fecharModalContrato = () => {
  showModalContrato.value = false;
  if (!showModalPod.value) cargaSelecionada.value = null;
};

const imprimirCertificado = () => window.print();

const getLogSelecionado = () => {
  if (!cargaSelecionada.value) return null;
  if (tipoCertificadoSelecionado.value === 'embarcador') {
    const log = cargaSelecionada.value.publicacao_log;
    if(log) log.data_evento = log.publicado_em; 
    return log;
  } else {
    const log = cargaSelecionada.value.aceite_log;
    if(log) log.data_evento = log.aceito_em;
    return log;
  }
};

const extrairTextoOriginal = () => {
    if (!cargaSelecionada.value) return '';
    const c = cargaSelecionada.value;
    if (tipoCertificadoSelecionado.value === 'embarcador') {
        const log = c.publicacao_log;
        if (!log) return "Registro de publicação indisponível.";
        return `TERMO PUBLICAÇÃO. O Embarcador declara a veracidade dos dados da carga ID ${c.id}, de ${c.cidade_origem} para ${c.cidade_destino}, referente ao produto ${c.produto} (${c.peso_kg}kg), pelo valor de R$ ${c.valor_frete}.`;
    }
};
// ----------------------------------------------

const fetchCargas = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/v1/embarcador/cargas?page=${page}`);
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
    await axios.delete(`/api/v1/embarcador/cargas/${id}`);
    fetchCargas(pagination.value.current_page);
  } catch (error) { alert('Erro ao tentar cancelar a carga.'); }
};

const abrirModalPod = (carga) => { cargaSelecionada.value = carga; showModalPod.value = true; };
const fecharModalPod = () => { showModalPod.value = false; if(!showModalContrato.value) cargaSelecionada.value = null; };

const aprovarPagamento = async () => {
  if (!confirm('Confirma aprovação do pagamento?')) return;
  try {
    await axios.post(`/api/v1/embarcador/cargas/${cargaSelecionada.value.id}/aprovar`);
    fecharModalPod();
    fetchCargas(pagination.value.current_page);
  } catch (e) { alert('Erro ao aprovar.'); }
};

const abrirDisputa = async () => {
  const motivo = prompt('Motivo da disputa:');
  if (!motivo) return;
  try {
    await axios.post(`/api/v1/embarcador/cargas/${cargaSelecionada.value.id}/disputa`, { motivo });
    fecharModalPod();
    fetchCargas(pagination.value.current_page);
  } catch (e) { alert('Erro ao abrir disputa.'); }
};

onMounted(() => {
  fetchCargas();

  if (window.Echo && auth.user?.embarcador?.id) {
    window.Echo.channel(`embarcador.${auth.user.embarcador.id}`)
      .listen('.CargaAtualizada', (e) => {
        if (!cargas.value) return;

        const index = cargas.value.findIndex(c => c.id === e.carga.id);
        if (index !== -1) {
          cargas.value[index] = e.carga;
        } else {
          cargas.value.unshift(e.carga);
        }
      });
  }
});

onBeforeUnmount(() => {
  if (window.Echo && auth.user?.embarcador?.id) {
    window.Echo.leaveChannel(`embarcador.${auth.user.embarcador.id}`);
  }
});
</script>

<style>
/* Estilos para ocultar o sistema e forçar apenas a impressão do modal */
@media print {
  body * { visibility: hidden; }
  #print-area, #print-area * { visibility: visible; }
  #print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 0;
  }
  @page { margin: 0.5cm; }
}
</style>