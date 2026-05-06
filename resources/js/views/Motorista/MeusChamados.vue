<template>
  <div class="space-y-6 h-[calc(100vh-120px)] flex flex-col md:flex-row gap-6">
    
    <div class="w-full md:w-1/3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-900 flex justify-between items-center shrink-0">
        <h3 class="text-sm font-black text-white uppercase tracking-wider">Meus Chamados</h3>
        <button @click="fetchTickets" :disabled="loading" class="text-gray-300 hover:text-white transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </button>
      </div>

      <div class="overflow-y-auto flex-1 p-2 bg-gray-50">
        <div v-if="loading && tickets?.length === 0" class="text-center py-10 text-gray-500 text-sm font-bold">
          A carregar histórico...
        </div>
        
        <div v-else-if="tickets?.length === 0" class="text-center py-12 px-4">
          <div class="bg-blue-50 text-blue-800 p-4 rounded-lg text-sm font-medium">
            Você não possui nenhum chamado de suporte aberto.
          </div>
        </div>

        <div v-else class="space-y-2">
          <div 
            v-for="ticket in tickets" 
            :key="ticket?.id"
            @click="abrirTicket(ticket)"
            :class="['p-4 rounded-lg cursor-pointer border transition-all duration-200', ticketSelecionado?.id === ticket?.id ? 'bg-blue-50 border-blue-300 shadow-sm' : 'bg-white border-gray-200 hover:border-blue-200 hover:shadow-sm']"
          >
            <div class="flex justify-between items-start mb-2">
              <span :class="getStatusBadge(ticket?.status)">{{ ticket?.status?.replace('_', ' ') || 'Processando' }}</span>
              <span class="text-[10px] text-gray-400 font-bold">{{ formatarData(ticket?.created_at) }}</span>
            </div>
            <h4 class="text-sm font-black text-gray-800 truncate" :title="ticket?.assunto">{{ ticket?.assunto }}</h4>
            <div class="flex justify-between items-center mt-2 text-xs">
              <span class="text-gray-500">{{ ticket?.categoria }}</span>
              <span v-if="ticket?.carga_id" class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded font-mono text-[10px] border border-gray-200">
                Carga #{{ ticket.carga_id }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="w-full md:w-2/3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
      
      <div v-if="!ticketSelecionado" class="flex-1 flex flex-col items-center justify-center text-gray-400 bg-gray-50">
        <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <p class="text-sm font-bold">Selecione um chamado na lista para ver a conversa.</p>
      </div>

      <template v-else>
        <div class="px-6 py-4 border-b border-gray-200 bg-white shrink-0 shadow-sm z-10">
          <div class="flex justify-between items-start">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Ticket #{{ ticketSelecionado.id }}</span>
                <span :class="getStatusBadge(ticketSelecionado.status)">{{ ticketSelecionado.status?.replace('_', ' ') || 'Processando' }}</span>
              </div>
              <h2 class="text-lg font-black text-gray-900">{{ ticketSelecionado.assunto }}</h2>
            </div>
            <div v-if="ticketSelecionado.staff" class="text-right">
              <span class="text-[10px] font-bold text-gray-500 uppercase block">Atendente Responsável</span>
              <span class="text-sm font-black text-blue-600">{{ ticketSelecionado.staff.name }}</span>
            </div>
            <div v-else class="text-right">
              <span class="text-[10px] font-bold text-gray-500 uppercase block">Atendimento</span>
              <span class="text-sm font-black text-orange-500">Na Fila de Triagem</span>
            </div>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 bg-gray-50 space-y-4" id="chat-container">
          <div v-if="loadingChat" class="text-center py-4">
            <span class="text-xs font-bold text-gray-400">A sincronizar mensagens...</span>
          </div>
          
          <template v-else>
            <div v-for="msg in ticketDetalhado?.messages" :key="msg.id" :class="['flex w-full', msg.user_id === ticketSelecionado.user_id ? 'justify-end' : 'justify-start']">
              <div :class="['max-w-[80%] rounded-2xl px-5 py-3 shadow-sm', msg.user_id === ticketSelecionado.user_id ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none']">
                <div class="flex justify-between items-end mb-1 space-x-4">
                  <span class="text-[10px] font-black uppercase opacity-75">
                    {{ msg.user_id === ticketSelecionado.user_id ? 'Você' : 'Suporte 123Fretei' }}
                  </span>
                  <span class="text-[9px] opacity-60">{{ formatarHora(msg.created_at) }}</span>
                </div>
                <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ msg.mensagem }}</p>
              </div>
            </div>
          </template>
        </div>

        <div class="p-4 bg-white border-t border-gray-200 shrink-0">
          <div v-if="ticketSelecionado.status === 'resolvido'" class="text-center p-3 bg-gray-100 rounded-lg text-sm text-gray-500 font-bold border border-gray-200">
            🔒 Este chamado foi encerrado pela equipa de suporte. Caso necessário, abra um novo ticket no mural.
          </div>
          
          <form v-else @submit.prevent="enviarResposta" class="flex gap-3 relative">
            <textarea 
              v-model="novaMensagem" 
              rows="2" 
              placeholder="Escreva a sua resposta..." 
              class="flex-1 border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none bg-gray-50 focus:bg-white transition-colors"
              @keydown.enter.prevent="enviarResposta"
            ></textarea>
            <button 
              type="submit" 
              :disabled="enviando || !novaMensagem.trim()" 
              class="px-6 bg-blue-600 text-white rounded-xl font-black text-sm hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center min-w-[120px] shadow-sm"
            >
              {{ enviando ? 'A Enviar...' : 'Responder ➔' }}
            </button>
          </form>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const tickets = ref([]);
const loading = ref(true);

const ticketSelecionado = ref(null);
const ticketDetalhado = ref(null);
const loadingChat = ref(false);

const novaMensagem = ref('');
const enviando = ref(false);

const scrollToBottom = async () => {
  await nextTick();
  const container = document.getElementById('chat-container');
  if (container) {
    container.scrollTop = container.scrollHeight;
  }
};

const getStatusBadge = (status) => {
  if (!status) return 'bg-gray-100 text-gray-800';
  const map = {
    aberto: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    em_atendimento: 'bg-blue-100 text-blue-800 border-blue-200',
    aguardando_cliente: 'bg-orange-100 text-orange-800 border-orange-200 text-[10px] animate-pulse',
    resolvido: 'bg-green-100 text-green-800 border-green-200',
  };
  return `px-2 py-0.5 text-[10px] font-black uppercase rounded border ${map[status] || 'bg-gray-100 text-gray-800'}`;
};

const formatarData = (dataStr) => {
  if (!dataStr) return '';
  const d = new Date(dataStr);
  return d.toLocaleDateString('pt-BR');
};

const formatarHora = (dataStr) => {
  if (!dataStr) return '';
  const d = new Date(dataStr);
  return `${d.toLocaleDateString('pt-BR')} às ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
};

const fetchTickets = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/suporte/tickets');
    tickets.value = res.data;
  } catch (error) {
    console.error('Erro ao carregar chamados', error);
    tickets.value = [];
  } finally {
    loading.value = false;
  }
};

const abrirTicket = async (ticketBase) => {
  if (!ticketBase?.id) return;
  ticketSelecionado.value = ticketBase;
  loadingChat.value = true;
  try {
    const res = await axios.get(`/api/v1/suporte/tickets/${ticketBase.id}`);
    ticketDetalhado.value = res.data;
    scrollToBottom();
  } catch (error) {
    console.error('Erro ao carregar detalhes do ticket', error);
  } finally {
    loadingChat.value = false;
  }
};

const enviarResposta = async () => {
  if (!novaMensagem.value.trim() || !ticketSelecionado.value?.id) return;
  
  enviando.value = true;
  try {
    await axios.post(`/api/v1/suporte/tickets/${ticketSelecionado.value.id}/responder`, {
      mensagem: novaMensagem.value
    });
    
    novaMensagem.value = '';
    await abrirTicket(ticketSelecionado.value);
    
    axios.get('/api/v1/suporte/tickets').then(res => {
      tickets.value = res.data;
      const t = tickets.value.find(t => t.id === ticketSelecionado.value.id);
      if(t) ticketSelecionado.value = t;
    });

  } catch (error) {
    alert(error.response?.data?.message || 'Erro de rede. A sua mensagem não foi enviada.');
  } finally {
    enviando.value = false;
  }
};

onMounted(() => {
  fetchTickets();
});
</script>