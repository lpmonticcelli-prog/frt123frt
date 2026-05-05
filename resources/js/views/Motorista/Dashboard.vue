<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
      
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800">Cargas Disponíveis na sua Região</h3>
        <button @click="fetchCargas(1)" :disabled="loading" class="text-sm font-bold text-blue-600 hover:text-blue-800 flex items-center disabled:opacity-50">
          {{ loading ? 'Atualizando...' : '↻ Atualizar Mural' }}
        </button>
      </div>

      <div v-if="loading && cargas?.length === 0" class="p-8 text-center text-gray-500 font-medium">
        Buscando fretes disponíveis...
      </div>

      <div v-else-if="!cargas || cargas?.length === 0" class="p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum frete encontrado</h3>
        <p class="mt-1 text-sm text-gray-500">No momento não há cargas compatíveis aguardando motorista.</p>
      </div>

      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-100">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Embarcador</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Rota</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Mercadoria</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Exigência</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Oferta</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200" :class="{ 'opacity-50': loading }">
            <tr v-for="carga in cargas" :key="carga?.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900">{{ carga?.embarcador?.razao_social || 'Empresa Privada' }}</div>
                <div class="text-xs font-medium text-gray-500 mt-1">Coleta: {{ formatData(carga?.data_coleta) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900">{{ carga?.cidade_origem }}/{{ carga?.uf_origem }}</div>
                <div class="text-sm text-gray-500">para {{ carga?.cidade_destino }}/{{ carga?.uf_destino }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ carga?.produto || 'N/A' }}</div>
                <div class="text-sm text-gray-500">{{ carga?.peso_kg || '0' }} kg</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 capitalize">{{ carga?.tipo_veiculo?.replace('_', ' ') || 'N/A' }}</div>
                <div class="text-sm text-gray-500 capitalize">{{ carga?.tipo_carroceria?.replace('_', ' ') || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-green-700">
                R$ {{ carga?.valor_frete || '0.00' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex justify-end space-x-2">
                  <button 
                    @click="abrirModalTicket(carga)" 
                    :disabled="!carga?.id"
                    class="inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-bold rounded shadow-sm focus:outline-none transition-colors disabled:opacity-50"
                    title="Dúvidas ou problemas com este frete? Abra um chamado"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                  </button>
                  <button 
                    @click="abrirModalAceite(carga)" 
                    :disabled="!carga?.id"
                    class="inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white font-bold rounded shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors disabled:opacity-50"
                  >
                    Analisar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="pagination.last_page > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando página <span class="font-bold">{{ pagination.current_page }}</span> de <span class="font-bold">{{ pagination.last_page }}</span>
            <span class="text-gray-500 ml-2">({{ pagination.total }} fretes no total)</span>
          </div>
          <div class="space-x-2">
            <button 
              @click="fetchCargas(pagination.current_page - 1)" 
              :disabled="pagination.current_page === 1 || loading"
              class="px-3 py-1 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Anterior
            </button>
            <button 
              @click="fetchCargas(pagination.current_page + 1)" 
              :disabled="pagination.current_page === pagination.last_page || loading"
              class="px-3 py-1 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Próxima
            </button>
          </div>
        </div>
      </template>
    </div>

    <div v-if="showModalAceite" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="fecharModalAceite"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-black text-gray-900" id="modal-title">Assinatura de Contrato de Frete</h3>
                <div class="mt-2 text-sm text-gray-500">Ao confirmar, você assinará digitalmente o compromisso de transporte desta carga.</div>

                <div class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4">
                  <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span class="block text-xs font-bold text-gray-500 uppercase">Embarcador</span>
                      <strong class="text-gray-900">{{ cargaSelecionada?.embarcador?.razao_social || 'Empresa Privada' }}</strong>
                    </div>
                    <div>
                      <span class="block text-xs font-bold text-gray-500 uppercase">Produto</span>
                      <strong class="text-gray-900">{{ cargaSelecionada?.produto }} ({{ cargaSelecionada?.peso_kg }}kg)</strong>
                    </div>
                    <div class="col-span-2">
                      <span class="block text-xs font-bold text-gray-500 uppercase">Rota Logística</span>
                      <strong class="text-gray-900">{{ cargaSelecionada?.cidade_origem }}/{{ cargaSelecionada?.uf_origem }} ➔ {{ cargaSelecionada?.cidade_destino }}/{{ cargaSelecionada?.uf_destino }}</strong>
                    </div>
                    <div class="col-span-2 border-t border-gray-200 pt-2 mt-1">
                      <span class="block text-xs font-bold text-gray-500 uppercase">Valor a Receber</span>
                      <strong class="text-green-600 text-xl font-black">R$ {{ cargaSelecionada?.valor_frete }}</strong>
                    </div>
                  </div>
                </div>

                <div class="mt-4 bg-yellow-50 p-3 rounded text-xs text-yellow-800 border border-yellow-200 text-justify">
                  <strong>DECLARAÇÃO LEGAL:</strong> Declaro que possuo CNH e RNTRC válidos para a categoria exigida e assumo a responsabilidade civil sobre a mercadoria a partir do momento da coleta. Meu endereço IP e informações de sessão serão registrados para fins de auditoria.
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
            <button type="button" @click="confirmarAceite" :disabled="actionLoading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-bold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
              {{ actionLoading ? 'Processando Assinatura...' : 'Assinar e Aceitar Frete' }}
            </button>
            <button type="button" @click="fecharModalAceite" :disabled="actionLoading" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
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
            <button type="button" @click="enviarTicket" :disabled="ticketLoading || !ticketForm.assunto || !ticketForm.mensagem" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
              {{ ticketLoading ? 'Enviando...' : 'Abrir Chamado' }}
            </button>
            <button type="button" @click="fecharModalTicket" :disabled="ticketLoading" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
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

const router = useRouter();
const cargas = ref([]);
const loading = ref(true);
const actionLoading = ref(false);
const ticketLoading = ref(false);

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
});

const showModalAceite = ref(false);
const showModalTicket = ref(false);
const cargaSelecionada = ref(null);

const ticketForm = ref({
  categoria: 'Dúvida Técnica',
  assunto: '',
  mensagem: ''
});

const formatData = (dataStr) => {
  if (!dataStr) return '--';
  try {
    const datePart = dataStr.split('T')[0]; 
    const [year, month, day] = datePart.split('-');
    return `${day}/${month}/${year}`;
  } catch (e) {
    return dataStr;
  }
};

const fetchCargas = async (page = 1) => {
  loading.value = true;
  try {
    // CORREÇÃO: Endpoint EXATO que está no routes/api.php
    const response = await axios.get(`/api/v1/motorista/cargas/disponiveis?page=${page}`);
    
    if (response.data && response.data.data) {
      cargas.value = response.data.data;
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total
      };
    } else {
      cargas.value = response.data || [];
    }
  } catch (error) {
    console.error('Erro ao carregar o mural:', error);
    cargas.value = [];
  } finally {
    loading.value = false;
  }
};

const abrirModalAceite = (carga) => {
  cargaSelecionada.value = carga;
  showModalAceite.value = true;
};

const fecharModalAceite = () => {
  showModalAceite.value = false;
  cargaSelecionada.value = null;
};

const confirmarAceite = async () => {
  if (!cargaSelecionada.value?.id) return;
  
  actionLoading.value = true;
  try {
    // CORREÇÃO: Endpoint EXATO que está no routes/api.php
    const response = await axios.post(`/api/v1/motorista/cargas/${cargaSelecionada.value.id}/aceitar`);
    alert(response.data.message || 'Frete assinado e aceito com sucesso!');
    fecharModalAceite();
    router.push({ name: 'MotoristaMeusFretes' });
  } catch (error) {
    console.error('Erro ao aceitar:', error);
    if (error.response?.status === 409) {
      alert('Infelizmente outro motorista foi mais rápido e já aceitou esta carga.');
      fecharModalAceite();
      fetchCargas(pagination.value.current_page);
    } else {
      alert(error.response?.data?.message || 'Erro de conexão ao tentar aceitar o frete.');
    }
  } finally {
    actionLoading.value = false;
  }
};

const abrirModalTicket = (carga) => {
  cargaSelecionada.value = carga;
  ticketForm.value = { categoria: 'Dúvida Técnica', assunto: '', mensagem: '' };
  showModalTicket.value = true;
};

const fecharModalTicket = () => {
  showModalTicket.value = false;
  cargaSelecionada.value = null;
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

    // CORREÇÃO: Endpoint EXATO que está no routes/api.php
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
  fetchCargas();
});
</script>