<template>
  <div class="space-y-6 relative">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800">Meus Fretes (Gestão de Viagem)</h3>
        <button @click="fetchMinhasCargas" class="text-sm font-bold text-blue-600 hover:text-blue-800">
          Atualizar Lista
        </button>
      </div>

      <div v-if="loading" class="p-8 text-center text-gray-500 font-medium">Buscando seus fretes...</div>

      <div v-else-if="!cargas || cargas?.length === 0" class="p-12 text-center">
        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum frete em andamento</h3>
        <p class="mt-1 text-sm text-gray-500">Vá ao Mural para pegar fretes.</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Rota / Embarcador</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Ações da Viagem</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="carga in cargas" :key="carga.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="['px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full capitalize', getStatusClass(carga.status)]">
                {{ carga.status?.replace('_', ' ') || 'Indefinido' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-gray-900">{{ carga.cidade_origem || 'N/A' }} → {{ carga.cidade_destino || 'N/A' }}</div>
              
              <div class="text-sm text-gray-500 flex items-center mb-2">
                {{ carga.embarcador?.razao_social || 'Empresa Privada' }}
                <span v-if="carga.aceite_log" title="Contrato Assinado" class="ml-1 text-green-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </span>
              </div>
              
              <button v-if="carga.aceite_log" @click="abrirModalContrato(carga, 'motorista')" class="inline-flex items-center px-2 py-1 bg-gray-800 text-white font-bold text-[10px] rounded hover:bg-gray-900 transition-colors shadow-sm mr-2 mt-1">
                🖨️ Documento & CIOT
              </button>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
              
              <button @click="abrirModalTicket(carga)" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-bold rounded shadow-sm transition-colors mr-2" title="Problemas com o frete? Abra um chamado">
                🎧 Ajuda
              </button>

              <template v-if="carga.status === 'aguardando_coleta'">
                <button @click="iniciarViagemEAbrirRastreador(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white font-bold rounded hover:bg-green-700 disabled:opacity-50 transition-colors">
                  {{ actionLoading === carga.id ? 'Processando...' : '▶ Iniciar Viagem' }}
                </button>
                <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-3 py-1.5 border border-red-600 text-red-600 font-bold rounded hover:bg-red-50 disabled:opacity-50 transition-colors ml-2">
                  Desistir
                </button>
              </template>
              
              <template v-else-if="carga.status === 'processando_aceite'">
                <span class="text-blue-600 font-bold text-sm">Aguardando ANTT...</span>
              </template>

              <template v-else-if="carga.status === 'em_transito'">
                <router-link :to="{ name: 'RastreadorFrete', params: { id: carga.id } }" class="inline-flex items-center px-3 py-2 bg-gray-900 text-white font-bold rounded hover:bg-gray-800 transition-colors mr-2">
                  📍 Abrir GPS
                </router-link>
                <button @click="abrirModalFinalizacao(carga)" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 transition-colors">
                  ✔ Comprovar Entrega
                </button>
              </template>

              <template v-else-if="carga.status === 'entregue' || carga.status === 'em_auditoria' || carga.status === 'finalizada'">
                <span class="text-green-600 font-bold text-sm">✔ Em Auditoria</span>
              </template>
              
              <template v-else-if="carga.status === 'em_disputa'">
                <span class="text-red-600 font-bold text-sm">⚠️ Bloqueado (Disputa)</span>
              </template>

            </td>
          </tr>
        </tbody>
      </table>
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
              Certificado de Assinatura Eletrônica (Transporte)
            </h3>
            
            <div class="space-y-6">
              <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 print:bg-white print:border-gray-400 print:border">
                <p class="text-sm text-yellow-800 font-medium print:text-gray-800">
                  Este certificado possui validade jurídica e serve como prova imutável. A autenticidade foi validada sistemicamente mediante login seguro pelo usuário responsável.
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
                      <td class="px-4 py-3 font-black text-green-900 uppercase">VALOR LÍQUIDO A RECEBER</td>
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

    <div v-if="showModalFinalizacao" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="fecharModalFinalizacao"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                  Comprovação de Entrega
                </h3>
                <div class="mt-2 text-sm text-gray-500">
                  Para finalizar a viagem, anexe as fotos obrigatórias. O upload será feito diretamente para o nosso storage seguro.
                </div>

                <div class="mt-4 space-y-4">
                  <div>
                    <label class="block text-sm font-bold text-gray-700">Foto do Canhoto Assinado <span class="text-red-500">*</span></label>
                    <input type="file" accept="image/*" capture="environment" @change="(e) => handleImageUpload(e, 'canhoto')" :disabled="actionLoading" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 disabled:opacity-50" />
                    <div v-if="isCompressingCanhoto" class="text-xs text-blue-600 mt-1 font-bold">Processando imagem nativa...</div>
                    <img v-if="previewCanhoto" :src="previewCanhoto" class="mt-2 h-32 object-contain border border-gray-200 rounded" />
                  </div>

                  <div>
                    <label class="block text-sm font-bold text-gray-700">Foto da Carga no Destino <span class="text-red-500">*</span></label>
                    <input type="file" accept="image/*" capture="environment" @change="(e) => handleImageUpload(e, 'carga')" :disabled="actionLoading" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 disabled:opacity-50" />
                    <div v-if="isCompressingCarga" class="text-xs text-blue-600 mt-1 font-bold">Processando imagem nativa...</div>
                    <img v-if="previewCarga" :src="previewCarga" class="mt-2 h-32 object-contain border border-gray-200 rounded" />
                  </div>
                </div>

              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="button" @click="submitFinalizacao" :disabled="!fotoCanhoto || !fotoCarga || actionLoading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
              {{ actionLoading ? uploadProgress + '% Enviando para Nuvem...' : 'Finalizar Entrega' }}
            </button>
            <button type="button" @click="fecharModalFinalizacao" :disabled="actionLoading" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModalTicket" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-ticket-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="fecharModalTicket"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg leading-6 font-black text-gray-900" id="modal-ticket-title">Precisa de Ajuda?</h3>
              <span class="bg-blue-100 text-blue-800 text-[10px] px-2 py-1 rounded font-bold">Carga #{{ cargaSelecionada?.id }}</span>
            </div>
            
            <p class="text-sm text-gray-500 mb-4">Abra um chamado diretamente com a nossa central de atendimento para esclarecer dúvidas sobre este frete específico.</p>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-bold text-gray-700">Categoria do Suporte</label>
                <select v-model="ticketForm.categoria" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                  <option value="Dúvida Técnica">Dúvida Técnica (Veículo, Rota, Peso)</option>
                  <option value="Financeiro">Dúvida Financeira (Pagamento, Taxas)</option>
                  <option value="Problema no App">Erro no Aplicativo</option>
                  <option value="Outros">Outros Assuntos</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700">Resumo (Assunto)</label>
                <input v-model="ticketForm.assunto" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: A altura livre do galpão comporta carreta LS?" />
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700">Mensagem Detalhada</label>
                <textarea v-model="ticketForm.mensagem" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Explique sua dúvida ao nosso time de suporte N1..."></textarea>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
            <button type="button" @click="enviarTicket" :disabled="ticketLoading || !ticketForm.assunto || !ticketForm.mensagem" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 transition-colors">
              {{ ticketLoading ? 'Enviando...' : 'Abrir Chamado' }}
            </button>
            <button type="button" @click="fecharModalTicket" :disabled="ticketLoading" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import imageCompression from 'browser-image-compression';

const router = useRouter();
const cargas = ref([]);
const loading = ref(true);
const actionLoading = ref(false);
const ticketLoading = ref(false);
const uploadProgress = ref(0);

const showModalTicket = ref(false);
const showModalFinalizacao = ref(false); 
const showModalContrato = ref(false); 
const cargaSelecionada = ref(null);
const tipoCertificadoSelecionado = ref('motorista');

const ticketForm = ref({
  categoria: 'Dúvida Técnica',
  assunto: '',
  mensagem: ''
});

// Uploads
const fotoCanhoto = ref(null);
const fotoCarga = ref(null);
const previewCanhoto = ref(null);
const previewCarga = ref(null);
const isCompressingCanhoto = ref(false);
const isCompressingCarga = ref(false);

const getStatusClass = (status) => {
  if (!status) return 'bg-gray-100 text-gray-800';
  const classes = { 
    aguardando_coleta: 'bg-yellow-100 text-yellow-800', 
    processando_aceite: 'bg-blue-100 text-blue-800',
    em_transito: 'bg-purple-100 text-purple-800', 
    em_auditoria: 'bg-yellow-100 text-yellow-800',
    entregue: 'bg-green-100 text-green-800', 
    finalizada: 'bg-green-100 text-green-800',
    em_disputa: 'bg-red-100 text-red-800' 
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatMoney = (value) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const fetchMinhasCargas = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/motorista/cargas/minhas');
    cargas.value = response.data.data ? response.data.data : response.data;
  } catch (error) { 
    console.error('Erro:', error); 
  } finally { 
    loading.value = false; 
  }
};

const iniciarViagemEAbrirRastreador = async (id) => {
  if (!confirm('Confirma que iniciou o deslocamento? A sua localização começará a ser transmitida para o Embarcador.')) return;
  actionLoading.value = id;
  try {
    await axios.post(`/api/v1/motorista/cargas/${id}/iniciar-viagem`);
    router.push({ name: 'RastreadorFrete', params: { id: id } });
  } catch (error) { 
    alert(error.response?.data?.message || 'Erro ao iniciar viagem.'); 
  } finally { 
    actionLoading.value = false; 
  }
};

const cancelarAceite = async (id) => {
  if (!confirm('Tem certeza que deseja desistir? O contrato de aceite gerado será anulado.')) return;
  actionLoading.value = id;
  try {
    await axios.post(`/api/v1/motorista/cargas/${id}/cancelar-aceite`);
    fetchMinhasCargas();
  } catch (error) { 
    alert(error.response?.data?.message || 'Erro ao cancelar o frete.'); 
  } finally { 
    actionLoading.value = false; 
  }
};

const abrirModalContrato = (carga, tipo) => {
  cargaSelecionada.value = carga;
  tipoCertificadoSelecionado.value = tipo;
  showModalContrato.value = true;
};

const fecharModalContrato = () => {
  showModalContrato.value = false;
  if (!showModalFinalizacao.value && !showModalTicket.value) cargaSelecionada.value = null;
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
        return `TERMO PUBLICAÇÃO. Carga ${c.id}, Origem ${c.cidade_origem}, Destino ${c.cidade_destino}, IP ${log.ip_address}, Data ${log.publicado_em}`;
    } else {
        const motoristaNome = c.motorista?.user?.name || 'N/A';
        const frete = parseFloat(c.valor_frete || 0).toFixed(2)?.replace('.', ',');
        return `CONTRATO DE TRANSPORTE. O motorista ${motoristaNome} aceita o frete ID ${c.id}, de ${c.cidade_origem} para ${c.cidade_destino}, pelo valor de R$ ${frete}.`;
    }
};

const abrirModalFinalizacao = (carga) => {
  cargaSelecionada.value = carga;
  showModalFinalizacao.value = true;
};

const fecharModalFinalizacao = () => {
  showModalFinalizacao.value = false;
  if (!showModalContrato.value && !showModalTicket.value) cargaSelecionada.value = null;
  fotoCanhoto.value = null;
  fotoCarga.value = null;
  previewCanhoto.value = null;
  previewCarga.value = null;
  uploadProgress.value = 0;
};

const handleImageUpload = async (event, tipo) => {
  const file = event.target.files[0];
  if (!file) return;

  const options = { maxSizeMB: 1, maxWidthOrHeight: 1600, useWebWorker: true };

  try {
    if (tipo === 'canhoto') isCompressingCanhoto.value = true;
    if (tipo === 'carga') isCompressingCarga.value = true;

    const compressedFile = await imageCompression(file, options);
    const finalFile = new File([compressedFile], compressedFile.name || "foto.jpg", { type: compressedFile.type });

    const reader = new FileReader();
    reader.onload = (e) => {
      if (tipo === 'canhoto') {
        fotoCanhoto.value = finalFile;
        previewCanhoto.value = e.target.result;
      } else {
        fotoCarga.value = finalFile;
        previewCarga.value = e.target.result;
      }
    };
    reader.readAsDataURL(finalFile);
  } catch (error) {
    alert("Erro ao comprimir imagem. Tente outra foto.");
  } finally {
    if (tipo === 'canhoto') isCompressingCanhoto.value = false;
    if (tipo === 'carga') isCompressingCarga.value = false;
  }
};

const getGeolocation = () => {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve({ coords: { latitude: null, longitude: null } });
            return;
        }
        navigator.geolocation.getCurrentPosition(resolve, (err) => {
            console.warn("[Segurança] Falha ao capturar GPS de auditoria:", err);
            resolve({ coords: { latitude: null, longitude: null } });
        }, { enableHighAccuracy: true, timeout: 10000 });
    });
};

const submitFinalizacao = async () => {
  if (!fotoCanhoto.value || !fotoCarga.value) {
    alert('As duas fotos são obrigatórias.');
    return;
  }
  
  actionLoading.value = true;
  uploadProgress.value = 10;

  try {
    const position = await getGeolocation();
    const resCanhoto = await axios.post(`/api/v1/motorista/cargas/${cargaSelecionada.value.id}/pod/url`);
    const signCanhoto = resCanhoto.data;
    uploadProgress.value = 30;

    await axios.put(signCanhoto.upload_url, fotoCanhoto.value, {
        headers: { 'Content-Type': fotoCanhoto.value.type },
        onUploadProgress: (progressEvent) => {
            uploadProgress.value = 30 + Math.round((progressEvent.loaded * 40) / progressEvent.total);
        }
    });

    uploadProgress.value = 90;

    await axios.post(`/api/v1/motorista/cargas/${cargaSelecionada.value.id}/pod/confirmar`, {
        file_path: signCanhoto.file_path,
        latitude_entrega: position.coords.latitude,
        longitude_entrega: position.coords.longitude
    });

    uploadProgress.value = 100;
    alert('Comprovante validado e carga enviada para a mesa de auditoria do Embarcador.');
    
    fecharModalFinalizacao();
    fetchMinhasCargas();

  } catch (error) {
    console.error('Falha de Storage/Rede:', error);
    alert(error.response?.data?.error || 'A conexão com os servidores da nuvem falhou. Mantenha a tela aberta e tente novamente.');
  } finally {
    actionLoading.value = false;
    uploadProgress.value = 0;
  }
};

const abrirModalTicket = (carga) => {
  cargaSelecionada.value = carga;
  ticketForm.value = { categoria: 'Dúvida Técnica', assunto: '', mensagem: '' };
  showModalTicket.value = true;
};
const fecharModalTicket = () => {
  showModalTicket.value = false;
  if (!showModalFinalizacao.value && !showModalContrato.value) cargaSelecionada.value = null;
};
const enviarTicket = async () => {
  if (!cargaSelecionada.value?.id) return;
  
  ticketLoading.value = true;
  try {
    const payload = {
      assunto: ticketForm.value.assunto,
      categoria: ticketForm.value.categoria,
      carga_id: cargaSelecionada.value.id,
      mensagem: ticketForm.value.mensagem
    };

    const response = await axios.post('/api/v1/suporte/tickets', payload);
    
    alert(response.data.message || 'Chamado aberto com sucesso! Nossa equipe retornará em breve.');
    fecharModalTicket();
  } catch (error) {
    console.error('Erro ao abrir ticket:', error);
    alert(error.response?.data?.message || 'Falha de comunicação ao tentar abrir o chamado.');
  } finally {
    ticketLoading.value = false;
  }
};

onMounted(() => {
  fetchMinhasCargas();
});
</script>

<style>
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