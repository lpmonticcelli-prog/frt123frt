<template>
  <div class="h-[calc(100vh-6rem)] max-w-7xl mx-auto flex gap-6">
    
    <div class="w-1/3 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
      <div class="p-4 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-lg font-black text-white tracking-tight">Fila de Triagem</h2>
          <p class="text-xs text-gray-400">Chamados aguardando atendimento</p>
        </div>
        <button @click="carregarFila" :disabled="loadingFila" class="p-2 text-gray-400 hover:text-white transition-colors" title="Atualizar Fila">
          <svg class="w-5 h-5" :class="{'animate-spin': loadingFila}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto bg-gray-50 p-3 space-y-3 custom-scrollbar">
        <div v-if="filaTickets?.length === 0" class="text-center py-10 text-gray-500 text-sm font-bold flex flex-col items-center">
          <span class="text-4xl mb-2">🎉</span>
          Fila zerada. Excelente trabalho!
        </div>

        <div v-for="ticket in filaTickets" :key="ticket.id" 
             @click="abrirTicket(ticket.id)"
             class="p-4 bg-white border rounded-lg cursor-pointer hover:shadow-md transition-all border-l-4"
             :class="[ticketAtivo?.id === ticket.id ? 'ring-2 ring-blue-500 shadow-md' : 'border-gray-200', getPriorityBorder(ticket.prioridade)]">
          <div class="flex justify-between items-start mb-2">
            <span :class="getPriorityBadge(ticket.prioridade)">{{ ticket.prioridade }}</span>
            <span class="text-xs text-gray-400 font-bold">{{ formatarData(ticket.created_at) }}</span>
          </div>
          <h3 class="text-sm font-black text-gray-900 truncate" :title="ticket.assunto">{{ ticket.assunto }}</h3>
          <p class="text-xs text-gray-500 mt-1">{{ ticket.categoria }} • {{ ticket.user?.name }}</p>
          <div v-if="ticket.carga_id" class="mt-2 text-[10px] uppercase font-black text-blue-600 bg-blue-50 inline-block px-2 py-1 rounded">
            Carga Vinculada #{{ ticket.carga_id }}
          </div>
        </div>
      </div>
    </div>

    <div class="w-2/3 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
      
      <div v-if="!ticketAtivo" class="flex-1 flex flex-col items-center justify-center text-gray-400 bg-gray-50">
        <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <p class="font-bold text-lg">Nenhum chamado selecionado</p>
        <p class="text-sm">Selecione um ticket na fila ao lado para iniciar o atendimento.</p>
      </div>

      <template v-else>
        <div class="p-5 border-b border-gray-200 bg-white">
          <div class="flex justify-between items-start">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-black text-gray-400">TICKET #{{ ticketAtivo.id }}</span>
                <span :class="getStatusBadge(ticketAtivo.status)">{{ ticketAtivo.status?.replace('_', ' ') }}</span>
              </div>
              <h2 class="text-xl font-black text-gray-900">{{ ticketAtivo.assunto }}</h2>
              <p class="text-sm text-gray-500 mt-1">Aberto por <span class="font-bold text-gray-700">{{ ticketAtivo.user?.name }}</span> ({{ ticketAtivo.user?.email }})</p>
            </div>
            
            <button v-if="!ticketAtivo.staff_id" @click="assumirTicket" :disabled="processando" 
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded shadow transition-colors disabled:opacity-50 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              Assumir Atendimento
            </button>
            <div v-else class="text-right">
              <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Atendente Responsável</p>
              <p class="text-sm font-black text-blue-700 mt-1">{{ ticketAtivo.staff?.name || 'Você' }}</p>
            </div>
          </div>
        </div>

        <div ref="chatContainer" class="flex-1 overflow-y-auto p-6 bg-gray-50 space-y-6 custom-scrollbar">
          <div v-for="msg in ticketAtivo.messages" :key="msg.id" class="flex flex-col" 
               :class="msg.user_id === ticketAtivo.user_id ? 'items-start' : 'items-end'">
            <div class="max-w-[75%]">
              <div class="text-[10px] font-bold text-gray-400 mb-1 px-1 uppercase tracking-wider"
                   :class="msg.user_id === ticketAtivo.user_id ? 'text-left' : 'text-right'">
                {{ msg.user_id === ticketAtivo.user_id ? 'Cliente (' + msg.user?.name + ')' : 'Suporte' }} • {{ formatarDataHora(msg.created_at) }}
              </div>
              <div class="p-4 rounded-2xl shadow-sm text-sm" 
                   :class="msg.user_id === ticketAtivo.user_id ? 'bg-white border border-gray-200 text-gray-800 rounded-tl-none' : 'bg-blue-600 text-white rounded-tr-none'">
                <p class="whitespace-pre-wrap leading-relaxed">{{ msg.mensagem }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="ticketAtivo.staff_id && ticketAtivo.status !== 'resolvido'" class="p-4 bg-white border-t border-gray-200">
          <textarea 
            v-model="novaMensagem" 
            @keydown.enter.prevent="enviarResposta"
            rows="3" 
            class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm p-3 border mb-3 resize-none transition-shadow" 
            placeholder="Digite a sua resposta (Pressione Enter para enviar)..."
          ></textarea>
          <div class="flex justify-between items-center">
            <button @click="fecharTicket" :disabled="processando" class="px-4 py-2 text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 text-sm font-bold rounded transition-colors disabled:opacity-50">
              Encerrar (Resolvido)
            </button>
            <button @click="enviarResposta" :disabled="processando || !novaMensagem.trim()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded shadow transition-colors disabled:opacity-50 flex items-center gap-2">
              {{ processando ? 'Enviando...' : 'Enviar Resposta' }}
              <svg v-if="!processando" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
          </div>
        </div>
        
        <div v-if="ticketAtivo.status === 'resolvido'" class="p-4 bg-green-50 border-t border-green-200 text-center text-green-800 font-bold text-sm">
          Este ticket foi marcado como resolvido e encerrado. Nenhuma nova mensagem pode ser enviada.
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const filaTickets = ref([]);
const ticketAtivo = ref(null);
const loadingFila = ref(true);
const processando = ref(false);
const novaMensagem = ref('');
const chatContainer = ref(null);

// Utilitários de Design Dinâmico
const getPriorityBadge = (prioridade) => {
  const map = {
    urgente: 'bg-red-100 text-red-800',
    alta: 'bg-orange-100 text-orange-800',
    normal: 'bg-blue-100 text-blue-800',
    baixa: 'bg-gray-200 text-gray-800'
  };
  return `px-2 py-0.5 text-[10px] font-black rounded uppercase ${map[prioridade]}`;
};

const getPriorityBorder = (prioridade) => {
  const map = { urgente: 'border-l-red-500', alta: 'border-l-orange-500', normal: 'border-l-blue-500', baixa: 'border-l-gray-300' };
  return map[prioridade];
};

const getStatusBadge = (status) => {
  const map = {
    aberto: 'bg-yellow-100 text-yellow-800',
    em_atendimento: 'bg-blue-100 text-blue-800',
    aguardando_cliente: 'bg-purple-100 text-purple-800',
    resolvido: 'bg-green-100 text-green-800'
  };
  return `px-2 py-1 text-[10px] font-black rounded uppercase ${map[status] || 'bg-gray-100 text-gray-800'}`;
};

const formatarData = (dataStr) => new Date(dataStr).toLocaleDateString('pt-BR');
const formatarDataHora = (dataStr) => new Date(dataStr).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });

// Descer a barra de rolagem do chat automaticamente
const scrollToBottom = () => {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
  });
};

// Integração com a API Backend
const carregarFila = async () => {
  loadingFila.value = true;
  try {
    const res = await axios.get('/api/admin/suporte/tickets');
    filaTickets.value = res.data.data; // .data devido ao paginate(20) do Laravel
  } catch (error) {
    console.error('Erro ao carregar a fila', error);
  } finally {
    loadingFila.value = false;
  }
};

const abrirTicket = async (id) => {
  try {
    const res = await axios.get(`/api/admin/suporte/tickets/${id}`);
    ticketAtivo.value = res.data;
    scrollToBottom();
  } catch (error) {
    alert('Erro ao abrir os detalhes do ticket.');
  }
};

const assumirTicket = async () => {
  processando.value = true;
  try {
    await axios.post(`/api/admin/suporte/tickets/${ticketAtivo.value.id}/assumir`);
    await abrirTicket(ticketAtivo.value.id); 
    // Remove visualmente da fila da esquerda, pois já é seu
    filaTickets.value = filaTickets.value.filter(t => t.id !== ticketAtivo.value.id);
  } catch (error) {
    alert(error.response?.data?.message || 'Falha ao assumir ticket.');
  } finally {
    processando.value = false;
  }
};

const enviarResposta = async () => {
  if (!novaMensagem.value.trim() || processando.value) return;
  processando.value = true;
  try {
    await axios.post(`/api/admin/suporte/tickets/${ticketAtivo.value.id}/responder`, {
      mensagem: novaMensagem.value
    });
    novaMensagem.value = '';
    await abrirTicket(ticketAtivo.value.id); // Recarrega o histórico
  } catch (error) {
    alert('Erro ao enviar mensagem.');
  } finally {
    processando.value = false;
  }
};

const fecharTicket = async () => {
  if (!confirm("Tem certeza que deseja encerrar este atendimento? O cliente não poderá mais responder.")) return;
  processando.value = true;
  try {
    await axios.post(`/api/admin/suporte/tickets/${ticketAtivo.value.id}/fechar`);
    ticketAtivo.value.status = 'resolvido';
  } catch (error) {
    alert('Erro ao finalizar ticket.');
  } finally {
    processando.value = false;
  }
};

onMounted(carregarFila);
</script>

<style scoped>
/* Scrollbar mais suave para o chat */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
  background: #94a3b8;
}
</style>