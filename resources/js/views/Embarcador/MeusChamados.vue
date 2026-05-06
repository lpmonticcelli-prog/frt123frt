<template>
  <div class="space-y-6 h-[calc(100vh-120px)] flex flex-col md:flex-row gap-6">
    
    <div class="w-full md:w-1/3 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
      <div class="px-6 py-4 border-b border-slate-200 bg-slate-900 flex justify-between items-center shrink-0">
        <h3 class="text-xs font-black text-white uppercase tracking-wider">Atendimento à Indústria</h3>
        <button @click="fetchTickets" :disabled="loading" class="text-slate-400 hover:text-white transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </button>
      </div>

      <div class="overflow-y-auto flex-1 p-2 bg-slate-50/50">
        <div v-if="loading && tickets?.length === 0" class="text-center py-10 text-slate-400 text-xs font-bold uppercase">Sincronizando Chamados...</div>
        
        <div v-else-if="tickets?.length === 0" class="text-center py-12 px-4">
          <div class="bg-white border border-slate-200 text-slate-500 p-6 rounded-xl text-sm font-medium shadow-sm italic">
            Nenhum ticket de suporte registado para a sua conta.
          </div>
        </div>

        <div v-else class="space-y-2">
          <div 
            v-for="ticket in tickets" :key="ticket.id" @click="abrirTicket(ticket)"
            :class="['p-4 rounded-xl cursor-pointer border transition-all duration-200', ticketSelecionado?.id === ticket.id ? 'bg-slate-900 border-slate-900 shadow-md transform scale-[1.02]' : 'bg-white border-slate-200 hover:border-slate-400 shadow-sm']"
          >
            <div class="flex justify-between items-start mb-2">
              <span :class="getStatusBadge(ticket.status)">{{ ticket.status?.replace('_', ' ') }}</span>
              <span :class="['text-[10px] font-bold uppercase', ticketSelecionado?.id === ticket.id ? 'text-slate-400' : 'text-slate-400']">{{ formatarData(ticket.created_at) }}</span>
            </div>
            <h4 :class="['text-sm font-black truncate', ticketSelecionado?.id === ticket.id ? 'text-white' : 'text-slate-800']">{{ ticket.assunto }}</h4>
            <div class="flex justify-between items-center mt-3">
              <span :class="['text-[10px] font-bold uppercase tracking-widest', ticketSelecionado?.id === ticket.id ? 'text-slate-500' : 'text-slate-400']">{{ ticket.categoria }}</span>
              <span v-if="ticket.carga_id" :class="['px-2 py-0.5 rounded font-mono text-[9px] border', ticketSelecionado?.id === ticket.id ? 'bg-slate-800 border-slate-700 text-slate-400' : 'bg-slate-50 border-slate-200 text-slate-500']">
                REF CARGA #{{ ticket.carga_id }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="w-full md:w-2/3 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
      <div v-if="!ticketSelecionado" class="flex-1 flex flex-col items-center justify-center text-slate-300 bg-slate-50/30">
        <svg class="w-20 h-20 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <p class="text-xs font-black uppercase tracking-widest">Selecione uma ocorrência para auditoria</p>
      </div>

      <template v-else>
        <div class="px-8 py-5 border-b border-slate-100 bg-white shrink-0 flex justify-between items-center shadow-sm">
          <div>
            <div class="flex items-center gap-3 mb-1">
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Protocolo #{{ ticketSelecionado.id }}</span>
              <span :class="getStatusBadge(ticketSelecionado.status)">{{ ticketSelecionado.status?.replace('_', ' ') }}</span>
            </div>
            <h2 class="text-lg font-black text-slate-900 tracking-tight">{{ ticketSelecionado.assunto }}</h2>
          </div>
          <div v-if="ticketSelecionado.staff" class="text-right">
            <span class="text-[9px] font-black text-slate-400 uppercase block tracking-widest mb-1">Especialista Alocado</span>
            <span class="text-sm font-black text-slate-900">{{ ticketSelecionado.staff.name }}</span>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-8 bg-slate-50/30 space-y-6" id="chat-container">
          <div v-for="msg in ticketDetalhado?.messages" :key="msg.id" :class="['flex w-full', msg.user_id === ticketSelecionado.user_id ? 'justify-end' : 'justify-start']">
            <div :class="['max-w-[85%] rounded-2xl px-6 py-4 shadow-sm border', msg.user_id === ticketSelecionado.user_id ? 'bg-slate-900 text-white border-slate-800 rounded-tr-none' : 'bg-white border-slate-200 text-slate-800 rounded-tl-none']">
              <div class="flex justify-between items-end mb-2 space-x-6">
                <span class="text-[9px] font-black uppercase tracking-widest opacity-60">
                  {{ msg.user_id === ticketSelecionado.user_id ? 'Sua Empresa' : 'Suporte 123Fretei' }}
                </span>
                <span class="text-[9px] font-bold opacity-40">{{ formatarHora(msg.created_at) }}</span>
              </div>
              <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ msg.mensagem }}</p>
            </div>
          </div>
        </div>

        <div class="p-6 bg-white border-t border-slate-100 shrink-0">
          <form v-if="ticketSelecionado.status !== 'resolvido'" @submit.prevent="enviarResposta" class="flex gap-4">
            <textarea v-model="novaMensagem" rows="2" placeholder="Digite sua réplica operacional..." class="flex-1 border border-slate-200 rounded-xl px-5 py-4 text-sm focus:ring-2 focus:ring-slate-900 outline-none bg-slate-50 focus:bg-white transition-all resize-none"></textarea>
            <button type="submit" :disabled="enviando || !novaMensagem.trim()" class="px-8 bg-slate-900 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition-all disabled:opacity-30 shadow-md">
              {{ enviando ? 'Enviando...' : 'Responder' }}
            </button>
          </form>
          <div v-else class="text-center p-4 bg-slate-50 rounded-xl text-xs font-bold text-slate-400 border border-slate-100 uppercase tracking-widest">
            🔒 Chamado encerrado. Histórico preservado para auditoria.
          </div>
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
  if (container) container.scrollTop = container.scrollHeight;
};

const getStatusBadge = (status) => {
  const map = {
    aberto: 'bg-amber-100 text-amber-800 border-amber-200',
    em_atendimento: 'bg-slate-100 text-slate-800 border-slate-300',
    aguardando_cliente: 'bg-orange-100 text-orange-800 border-orange-200 animate-pulse',
    resolvido: 'bg-green-100 text-green-800 border-green-200',
  };
  return `px-2 py-0.5 text-[9px] font-black uppercase rounded border ${map[status] || 'bg-gray-100'}`;
};

const formatarData = (d) => d ? new Date(d).toLocaleDateString('pt-PT') : '';
const formatarHora = (d) => d ? new Date(d).toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' }) : '';

const fetchTickets = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/suporte/tickets');
    tickets.value = res.data;
  } catch (e) { console.error(e); } finally { loading.value = false; }
};

const abrirTicket = async (ticket) => {
  ticketSelecionado.value = ticket;
  loadingChat.value = true;
  try {
    const res = await axios.get(`/api/v1/suporte/tickets/${ticket.id}`);
    ticketDetalhado.value = res.data;
    scrollToBottom();
  } catch (e) { console.error(e); } finally { loadingChat.value = false; }
};

const enviarResposta = async () => {
  if (!novaMensagem.value.trim() || !ticketSelecionado.value) return;
  enviando.value = true;
  try {
    await axios.post(`/api/v1/suporte/tickets/${ticketSelecionado.value.id}/responder`, { mensagem: novaMensagem.value });
    novaMensagem.value = '';
    await abrirTicket(ticketSelecionado.value);
    fetchTickets();
  } catch (e) { alert('Erro ao enviar.'); } finally { enviando.value = false; }
};

onMounted(fetchTickets);
</script>