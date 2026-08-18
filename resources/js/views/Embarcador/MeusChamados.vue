<template>
  <!-- LIMITA A ALTURA PARA FAZER UM LAYOUT ESTILO APP NATIVO -->
  <div class="h-[calc(100vh-100px)] flex flex-col md:flex-row gap-6 p-4 sm:p-6 bg-slate-50 w-full relative">
    
    <!-- COLUNA ESQUERDA: LISTA DE TICKETS -->
    <div class="w-full md:w-1/3 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
      <div class="px-6 py-5 border-b border-slate-200 bg-white flex justify-between items-center shrink-0 shadow-sm z-10">
        <div>
          <h3 class="text-lg font-black text-slate-900 tracking-tight">Atendimento</h3>
          <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">Caixa de Entrada</p>
        </div>
        <button @click="fetchTickets" :disabled="loading" class="text-slate-400 hover:text-[#035D29] bg-slate-50 p-2 rounded-lg border border-slate-200 transition-colors focus:outline-none focus:ring-2 focus:ring-[#035D29]">
          <svg class="w-5 h-5" :class="{'animate-spin text-[#ff5500]': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </button>
      </div>

      <div class="overflow-y-auto flex-1 p-4 bg-slate-50 scrollbar-clinical">
        <!-- VAZIO OU LOADING -->
        <div v-if="loading && tickets?.length === 0" class="text-center py-12 flex flex-col items-center">
            <svg class="w-8 h-8 animate-spin text-[#ff5500] mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="text-slate-500 text-xs font-bold uppercase tracking-widest">Sincronizando Chamados...</span>
        </div>
        
        <div v-else-if="tickets?.length === 0" class="text-center py-12 px-4">
          <div class="bg-white border border-slate-200 border-dashed text-slate-500 p-8 rounded-2xl text-sm font-medium shadow-sm flex flex-col items-center">
            <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2-2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            Nenhum ticket de suporte registado na sua conta.
          </div>
        </div>

        <!-- LISTAGEM -->
        <div v-else class="space-y-3">
          <div 
            v-for="ticket in tickets" :key="ticket.id" @click="abrirTicket(ticket)"
            :class="[
              'p-5 rounded-2xl cursor-pointer border transition-all duration-200', 
              ticketSelecionado?.id === ticket.id 
                ? 'bg-white border-[#035D29] ring-1 ring-[#035D29] shadow-md transform scale-[1.01]' 
                : 'bg-white border-slate-200 hover:border-slate-300 shadow-sm'
            ]"
          >
            <div class="flex justify-between items-start mb-3">
              <span :class="getStatusBadge(ticket.status)">{{ ticket.status?.replace('_', ' ') }}</span>
              <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">{{ formatarData(ticket.created_at) }}</span>
            </div>
            <h4 class="text-sm font-black text-slate-900 line-clamp-2 leading-tight">{{ ticket.assunto }}</h4>
            <div class="flex justify-between items-center mt-4">
              <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ ticket.categoria }}</span>
              <span v-if="ticket.carga_id" class="px-2.5 py-1 rounded-md font-mono text-[9px] border font-bold bg-slate-50 border-slate-200 text-slate-600">
                CARGA #{{ ticket.carga_id }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- COLUNA DIREITA: O CHAT / TELA DE DETALHES -->
    <div class="w-full md:w-2/3 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
      
      <div v-if="!ticketSelecionado" class="flex-1 flex flex-col items-center justify-center text-slate-300 bg-slate-50/50">
        <svg class="w-24 h-24 mb-6 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <p class="text-xs font-black uppercase tracking-widest text-slate-400">Selecione uma ocorrência para visualizar</p>
      </div>

      <template v-else>
        <!-- CABEÇALHO DO TICKET -->
        <div class="px-8 py-6 border-b border-slate-200 bg-white shrink-0 flex flex-col sm:flex-row justify-between items-start sm:items-center shadow-sm z-10">
          <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3 mb-2">
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Protocolo #{{ ticketSelecionado.id }}</span>
              <span :class="getStatusBadge(ticketSelecionado.status)">{{ ticketSelecionado.status?.replace('_', ' ') }}</span>
            </div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight leading-tight">{{ ticketSelecionado.assunto }}</h2>
          </div>
          <div v-if="ticketSelecionado.staff" class="text-left sm:text-right bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
            <span class="text-[9px] font-black text-[#ff5500] uppercase block tracking-widest mb-0.5">Especialista Alocado</span>
            <span class="text-sm font-bold text-slate-800">{{ ticketSelecionado.staff.name }}</span>
          </div>
          <div v-else class="text-left sm:text-right bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
            <span class="text-[9px] font-black text-slate-400 uppercase block tracking-widest mb-0.5">Status da Fila</span>
            <span class="text-sm font-bold text-slate-600">Aguardando Análise</span>
          </div>
        </div>

        <!-- MENSAGENS -->
        <div class="flex-1 overflow-y-auto p-6 sm:p-8 bg-slate-50/50 space-y-6 scrollbar-clinical" id="chat-container">
          
          <div v-if="loadingChat" class="flex justify-center py-10">
              <svg class="w-8 h-8 animate-spin text-[#035D29]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          </div>

          <div v-else v-for="msg in ticketDetalhado?.messages" :key="msg.id" :class="['flex w-full', msg.user_id === ticketSelecionado.user_id ? 'justify-end' : 'justify-start']">
            <div :class="['max-w-[90%] sm:max-w-[75%] rounded-2xl px-6 py-4 shadow-sm border', msg.user_id === ticketSelecionado.user_id ? 'bg-[#035D29] text-white border-[#023818] rounded-tr-none' : 'bg-white border-slate-200 text-slate-800 rounded-tl-none']">
              <div class="flex justify-between items-end mb-3 space-x-6">
                <span class="text-[9px] font-black uppercase tracking-widest opacity-80">
                  {{ msg.user_id === ticketSelecionado.user_id ? 'Sua Empresa' : 'Suporte 123Fretei' }}
                </span>
                <span class="text-[10px] font-bold opacity-60">{{ formatarHora(msg.created_at) }}</span>
              </div>
              <p class="text-sm leading-relaxed whitespace-pre-wrap font-medium">{{ msg.mensagem }}</p>
            </div>
          </div>
        </div>

        <!-- CAMPO DE RESPOSTA -->
        <div class="p-5 bg-white border-t border-slate-200 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]">
          <form v-if="ticketSelecionado.status !== 'resolvido'" @submit.prevent="enviarResposta" class="flex flex-col sm:flex-row gap-4">
            <textarea 
              v-model="novaMensagem" 
              rows="2" 
              placeholder="Digite sua mensagem ou réplica..." 
              class="flex-1 border border-slate-300 rounded-xl px-5 py-4 text-sm focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] outline-none bg-slate-50 focus:bg-white transition-all resize-none shadow-inner"
            ></textarea>
            <button 
              type="submit" 
              :disabled="enviando || !novaMensagem.trim()" 
              class="w-full sm:w-auto px-8 bg-[#035D29] text-white rounded-xl font-bold text-sm hover:bg-[#023818] transition-all disabled:opacity-50 shadow-md flex items-center justify-center"
            >
              {{ enviando ? 'Enviando...' : 'Enviar Mensagem' }}
            </button>
          </form>
          
          <div v-else class="text-center p-5 bg-slate-50 rounded-xl text-xs font-bold text-slate-500 border border-slate-200 uppercase tracking-widest flex items-center justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            Chamado encerrado. Histórico preservado para auditoria.
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

// MAPEAMENTO DE CORES DE STATUS MODERNIZADO
const getStatusBadge = (status) => {
  const map = {
    aberto: 'bg-amber-100 text-amber-800 border-amber-200',
    em_atendimento: 'bg-blue-100 text-blue-800 border-blue-200',
    aguardando_cliente: 'bg-[#ff5500]/10 text-[#ff5500] border-[#ff5500]/30 animate-pulse',
    resolvido: 'bg-emerald-100 text-emerald-800 border-emerald-200',
  };
  return `px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-md border ${map[status] || 'bg-slate-100 text-slate-600 border-slate-200'}`;
};

const formatarData = (d) => d ? new Date(d).toLocaleDateString('pt-BR') : '';
const formatarHora = (d) => d ? new Date(d).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '';

const fetchTickets = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/suporte/tickets');
    tickets.value = res.data;
  } catch (e) { 
      console.error(e); 
  } finally { 
      loading.value = false; 
  }
};

const abrirTicket = async (ticket) => {
  ticketSelecionado.value = ticket;
  loadingChat.value = true;
  try {
    const res = await axios.get(`/api/v1/suporte/tickets/${ticket.id}`);
    ticketDetalhado.value = res.data;
    scrollToBottom();
  } catch (e) { 
      console.error(e); 
  } finally { 
      loadingChat.value = false; 
  }
};

const enviarResposta = async () => {
  if (!novaMensagem.value.trim() || !ticketSelecionado.value) return;
  enviando.value = true;
  try {
    await axios.post(`/api/v1/suporte/tickets/${ticketSelecionado.value.id}/mensagens`, { mensagem: novaMensagem.value });
    novaMensagem.value = '';
    await abrirTicket(ticketSelecionado.value);
    fetchTickets();
  } catch (e) { 
      alert('Erro ao enviar a mensagem. Verifique a sua conexão.'); 
  } finally { 
      enviando.value = false; 
  }
};

onMounted(fetchTickets);
</script>

<style scoped>
.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px;}
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>