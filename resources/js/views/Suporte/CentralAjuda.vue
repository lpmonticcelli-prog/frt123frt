<template>
  <div class="max-w-5xl mx-auto space-y-6">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Central de Ajuda</h2>
        <p class="text-sm text-gray-500 mt-1">Como podemos ajudar na sua operação hoje?</p>
      </div>
      <button v-if="view !== 'novo'" @click="iniciarNovoChamado" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded shadow transition-colors">
        + Abrir Novo Chamado
      </button>
      <button v-else @click="view = 'lista'" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-bold rounded transition-colors">
        Voltar para Meus Chamados
      </button>
    </div>

    <div v-if="view === 'lista'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
      <div v-if="loading" class="flex justify-center p-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
      
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Chamado</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Categoria</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ação</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="meusTickets.length === 0">
            <td colspan="4" class="px-6 py-12 text-center text-gray-500">Você não tem nenhum chamado aberto.</td>
          </tr>
          <tr v-for="ticket in meusTickets" :key="ticket.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4">
              <div class="text-sm font-black text-gray-900">#{{ ticket.id }} - {{ ticket.assunto }}</div>
              <div class="text-xs text-gray-500">Aberto em {{ formatarData(ticket.created_at) }}</div>
            </td>
            <td class="px-6 py-4">
              <span class="text-sm text-gray-700">{{ ticket.categoria }}</span>
              <div v-if="ticket.carga_id" class="text-[10px] font-bold text-blue-600 mt-1 uppercase">Carga #{{ ticket.carga_id }}</div>
            </td>
            <td class="px-6 py-4 text-center">
              <span :class="getStatusBadge(ticket.status)">{{ ticket.status.replace('_', ' ') }}</span>
            </td>
            <td class="px-6 py-4 text-right">
              <button @click="abrirDetalhes(ticket.id)" class="text-blue-600 hover:text-blue-800 text-sm font-bold">
                Ver Interação ➔
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="view === 'novo'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 animate-fade-in max-w-3xl mx-auto">
      <h3 class="text-lg font-black text-gray-900 mb-6 border-b pb-4">Descreva o seu problema</h3>
      
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-3">1. Qual a natureza do seu contato?</label>
          <div class="grid grid-cols-2 gap-3">
            <button v-for="cat in categorias" :key="cat" @click="formNovo.categoria = cat" type="button" 
                    class="p-4 border rounded-lg text-left transition-all"
                    :class="formNovo.categoria === cat ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200 hover:border-blue-300'">
              <p class="font-bold text-gray-900 text-sm">{{ cat }}</p>
            </button>
          </div>
        </div>

        <div v-if="formNovo.categoria === 'Disputa de Frete' || formNovo.categoria === 'Problema Operacional'" class="p-4 bg-orange-50 border border-orange-200 rounded-lg animate-fade-in">
          <label class="block text-sm font-bold text-orange-800 mb-1">Carga relacionada (Opcional, mas agiliza o atendimento)</label>
          <input v-model="formNovo.carga_id" type="number" class="w-full border-orange-300 rounded focus:ring-orange-500 focus:border-orange-500 text-sm p-2 bg-white" placeholder="Digite o ID da Carga (Ex: 1045)">
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">2. Resumo do Assunto</label>
          <input v-model="formNovo.assunto" type="text" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-3 border" placeholder="Ex: Motorista não chegou ao local de coleta">
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">3. Detalhes (O que aconteceu?)</label>
          <textarea v-model="formNovo.mensagem" rows="5" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-3 border resize-none" placeholder="Explique a situação o mais detalhado possível..."></textarea>
        </div>

        <div class="pt-4 flex justify-end">
          <button @click="enviarNovoChamado" :disabled="processando || !formNovo.categoria || !formNovo.assunto || !formNovo.mensagem" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-lg shadow-md transition-colors disabled:opacity-50">
            {{ processando ? 'A enviar...' : 'Abrir Chamado Seguro' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="view === 'detalhe' && ticketAtivo" class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[600px] animate-fade-in">
      
      <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center rounded-t-xl">
        <div class="flex items-center gap-4">
          <button @click="view = 'lista'" class="text-gray-500 hover:text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          </button>
          <div>
            <h3 class="font-black text-gray-900 text-lg">#{{ ticketAtivo.id }} - {{ ticketAtivo.assunto }}</h3>
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider text-blue-600">Suporte N1 • Em Atendimento</p>
          </div>
        </div>
        <span :class="getStatusBadge(ticketAtivo.status)">{{ ticketAtivo.status.replace('_', ' ') }}</span>
      </div>

      <div class="flex-1 overflow-y-auto p-6 bg-white space-y-4">
        <div v-for="msg in ticketAtivo.messages" :key="msg.id" class="flex flex-col" 
             :class="msg.user_id === ticketAtivo.user_id ? 'items-end' : 'items-start'">
          <div class="max-w-[75%]">
            <div class="text-[10px] font-bold text-gray-400 mb-1 px-1 uppercase" :class="{'text-right': msg.user_id === ticketAtivo.user_id}">
              {{ msg.user_id === ticketAtivo.user_id ? 'Você' : 'Suporte 123fretei' }} • {{ formatarDataHora(msg.created_at) }}
            </div>
            <div class="p-4 rounded-2xl shadow-sm text-sm border" 
                 :class="msg.user_id === ticketAtivo.user_id ? 'bg-blue-600 border-blue-700 text-white rounded-tr-none' : 'bg-gray-100 border-gray-200 text-gray-800 rounded-tl-none'">
              <p class="whitespace-pre-wrap leading-relaxed">{{ msg.mensagem }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="ticketAtivo.status !== 'resolvido' && ticketAtivo.status !== 'fechado'" class="p-4 bg-gray-50 border-t border-gray-200 rounded-b-xl">
        <div class="flex gap-3">
          <textarea v-model="novaMensagem" rows="2" class="flex-1 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm p-3 border resize-none" placeholder="Escreva sua resposta..."></textarea>
          <button @click="enviarResposta" :disabled="processando || !novaMensagem.trim()" class="px-6 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-lg transition-colors disabled:opacity-50">
            Enviar
          </button>
        </div>
      </div>
      <div v-else class="p-4 bg-green-50 text-center text-green-800 font-bold text-sm border-t border-green-200 rounded-b-xl">
        Este chamado foi resolvido e encerrado. Agradecemos o contato!
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const view = ref('lista'); // lista, novo, detalhe
const meusTickets = ref([]);
const ticketAtivo = ref(null);
const loading = ref(true);
const processando = ref(false);
const novaMensagem = ref('');

const categorias = [
  'Disputa de Frete',
  'Problema Operacional',
  'Financeiro',
  'Dúvida Técnica (Aplicativo)'
];

const formNovo = ref({
  categoria: '',
  carga_id: null,
  assunto: '',
  mensagem: ''
});

// Utilitários Visuais
const getStatusBadge = (status) => {
  const map = {
    aberto: 'bg-yellow-100 text-yellow-800',
    em_atendimento: 'bg-blue-100 text-blue-800',
    aguardando_cliente: 'bg-orange-100 text-orange-800 border border-orange-300',
    resolvido: 'bg-green-100 text-green-800'
  };
  return `px-2 py-1 text-[10px] font-black rounded-full uppercase ${map[status] || 'bg-gray-100 text-gray-800'}`;
};

const formatarData = (dataStr) => new Date(dataStr).toLocaleDateString('pt-BR');
const formatarDataHora = (dataStr) => new Date(dataStr).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });

// API Calls
const carregarMeusTickets = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/suporte/tickets');
    meusTickets.value = res.data;
  } catch (error) {
    console.error('Erro ao carregar tickets', error);
  } finally {
    loading.value = false;
  }
};

const iniciarNovoChamado = () => {
  formNovo.value = { categoria: '', carga_id: null, assunto: '', mensagem: '' };
  view.value = 'novo';
};

const enviarNovoChamado = async () => {
  processando.value = true;
  try {
    const res = await axios.post('/api/suporte/tickets', formNovo.value);
    alert(res.data.message);
    await carregarMeusTickets();
    view.value = 'lista';
  } catch (error) {
    alert(error.response?.data?.message || 'Erro ao abrir chamado.');
  } finally {
    processando.value = false;
  }
};

const abrirDetalhes = async (id) => {
  loading.value = true;
  try {
    const res = await axios.get(`/api/suporte/tickets/${id}`);
    ticketAtivo.value = res.data;
    view.value = 'detalhe';
  } catch (error) {
    alert('Erro ao carregar histórico.');
  } finally {
    loading.value = false;
  }
};

const enviarResposta = async () => {
  if (!novaMensagem.value.trim()) return;
  processando.value = true;
  try {
    await axios.post(`/api/suporte/tickets/${ticketAtivo.value.id}/responder`, {
      mensagem: novaMensagem.value
    });
    novaMensagem.value = '';
    await abrirDetalhes(ticketAtivo.value.id); // Recarrega o chat
    
    // Atualiza o status na lista localmente para não precisar dar load nela toda
    const ticketNaLista = meusTickets.value.find(t => t.id === ticketAtivo.value.id);
    if(ticketNaLista) ticketNaLista.status = ticketAtivo.value.status;
    
  } catch (error) {
    alert('Erro ao enviar mensagem.');
  } finally {
    processando.value = false;
  }
};

onMounted(carregarMeusTickets);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>