<template>
  <!-- Envolvemos a tela para ocupar o espaço do Layout, adaptando para mobile e desktop -->
  <div class="flex flex-col lg:flex-row gap-4 sm:gap-6 h-[calc(100dvh-140px)] lg:h-[calc(100dvh-8rem)] animate-fade-in pb-4 lg:pb-0 px-2 sm:px-0">
    
    <!-- COLUNA ESQUERDA: LISTA DE CHAMADOS -->
    <div class="w-full lg:w-1/3 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-[350px] shrink-0 lg:h-full lg:shrink">
      <div class="px-5 py-5 border-b border-slate-800 bg-slate-900 flex justify-between items-center shrink-0 z-10 shadow-md">
        <h3 class="text-[11px] font-black text-white uppercase tracking-widest flex items-center">
          <svg class="w-4 h-4 mr-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
          Meus Chamados
        </h3>
        <div class="flex items-center gap-3">
          <button @click="showModalNovo = true" class="bg-[#035D29] hover:bg-[#023818] text-white px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-transform active:scale-95 flex items-center shadow-sm">
            + Novo
          </button>
          <button @click="fetchTickets" :disabled="loading" class="text-slate-400 hover:text-white transition-colors focus:outline-none p-1" title="Atualizar Lista">
            <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          </button>
        </div>
      </div>

      <div class="overflow-y-auto flex-1 p-3 sm:p-4 bg-slate-50 scrollbar-clinical shadow-inner relative">
        <!-- ESTADO: CARREGANDO -->
        <div v-if="loading && (!tickets || tickets.length === 0)" class="text-center py-12 text-slate-500 text-sm font-bold flex flex-col items-center">
          <svg class="w-8 h-8 animate-spin mb-3 text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          Carregando histórico...
        </div>
        
        <!-- ESTADO: VAZIO -->
        <div v-else-if="!tickets || tickets.length === 0" class="text-center py-12 px-4 flex flex-col items-center">
          <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center mb-3">
             <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
          </div>
          <div class="text-slate-500 text-sm font-bold">
            Você não tem chamados abertos.
          </div>
        </div>

        <!-- LISTA DE TICKETS -->
        <div v-else class="space-y-3">
          <div 
            v-for="ticket in (tickets || [])" 
            :key="ticket?.id"
            @click="abrirTicket(ticket)"
            :class="['p-4 rounded-2xl cursor-pointer border-2 transition-all duration-200 block', ticketSelecionado?.id === ticket?.id ? 'bg-emerald-50 border-[#035D29]/40 shadow-md ring-1 ring-[#035D29]/20 scale-[0.98]' : 'bg-white border-slate-200 hover:border-slate-300 hover:shadow-sm']"
          >
            <div class="flex justify-between items-start mb-3 gap-2">
              <span :class="getStatusBadge(ticket?.status)">{{ formatarStatus(ticket?.status) }}</span>
              <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest shrink-0 mt-1">{{ formatarData(ticket?.created_at) }}</span>
            </div>
            <h4 class="text-sm font-black text-slate-900 line-clamp-2 leading-tight mb-3" :title="ticket?.assunto">#{{ ticket?.id }} - {{ ticket?.assunto || 'Sem assunto' }}</h4>
            <div class="flex justify-between items-center pt-3 border-t border-slate-100 text-xs">
              <span class="text-slate-500 font-bold truncate pr-2">{{ ticket?.categoria || 'Geral' }}</span>
              <span v-if="ticket?.carga_id" class="px-2 py-1 bg-slate-100 text-slate-600 rounded border border-slate-200 font-black text-[9px] uppercase tracking-widest shrink-0">
                Carga #{{ ticket.carga_id }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- COLUNA DIREITA: ÁREA DO CHAT -->
    <div class="w-full lg:w-2/3 bg-slate-100 rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-[500px] lg:h-full shrink-0 lg:shrink relative">
      
      <!-- ESTADO: NENHUM SELECIONADO -->
      <div v-if="!ticketSelecionado" class="flex-1 flex flex-col items-center justify-center text-slate-400 bg-slate-50 p-6 text-center shadow-inner relative">
        <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 20px 20px;"></div>
        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center border border-slate-200 shadow-sm mb-4 relative z-10">
          <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        </div>
        <p class="text-sm sm:text-base font-black text-slate-600 relative z-10">Selecione um chamado na lista<br>para abrir a conversa.</p>
      </div>

      <!-- ESTADO: TICKET ABERTO -->
      <template v-else>
        <!-- HEADER DO CHAT -->
        <div class="px-5 py-4 sm:px-6 border-b border-slate-800 bg-slate-900 shrink-0 shadow-md z-20">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 mb-1.5">
                <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest bg-emerald-900/50 px-2 py-0.5 rounded">Ticket #{{ ticketSelecionado.id }}</span>
                <span :class="getStatusBadge(ticketSelecionado.status)">{{ formatarStatus(ticketSelecionado.status) }}</span>
              </div>
              <h2 class="text-base sm:text-lg font-black text-white tracking-tight truncate">{{ ticketSelecionado.assunto }}</h2>
            </div>
            <div v-if="ticketSelecionado.staff" class="text-left sm:text-right shrink-0 bg-slate-800 sm:bg-transparent p-3 sm:p-0 rounded-xl w-full sm:w-auto border sm:border-transparent border-slate-700">
              <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Atendente</span>
              <span class="text-sm font-black text-emerald-400">{{ ticketSelecionado.staff.name }}</span>
            </div>
            <div v-else class="text-left sm:text-right shrink-0 bg-slate-800 sm:bg-transparent p-3 sm:p-0 rounded-xl w-full sm:w-auto border sm:border-transparent border-slate-700">
              <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Atendimento</span>
              <span class="text-sm font-black text-amber-400 animate-pulse">Na Fila de Triagem</span>
            </div>
          </div>
        </div>

        <!-- MENSAGENS -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-slate-100 space-y-5 scrollbar-clinical shadow-inner relative z-10" id="chat-container">
          <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 20px 20px;"></div>
          
          <div v-if="loadingChat" class="text-center py-10 flex flex-col items-center relative z-10">
            <svg class="w-8 h-8 animate-spin mb-3 text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Sincronizando mensagens...</span>
          </div>
          
          <template v-else>
            <div v-for="msg in (ticketDetalhado?.messages || [])" :key="msg?.id" :class="['flex w-full relative z-10', msg?.user_id === ticketSelecionado.user_id ? 'justify-end' : 'justify-start']">
              <div :class="['max-w-[85%] sm:max-w-[75%] rounded-2xl px-5 py-4 shadow-sm', msg?.user_id === ticketSelecionado.user_id ? 'bg-[#035D29] text-white rounded-br-none' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-none']">
                <div class="flex justify-between items-end mb-2 space-x-4">
                  <span class="text-[10px] font-black uppercase tracking-widest" :class="msg?.user_id === ticketSelecionado.user_id ? 'text-emerald-300' : 'text-[#ff5500]'">
                    {{ msg?.user_id === ticketSelecionado.user_id ? 'Você' : 'Suporte' }}
                  </span>
                  <span class="text-[9px] font-bold opacity-60 tabular-nums">{{ formatarHora(msg?.created_at) }}</span>
                </div>
                <p class="text-sm sm:text-base whitespace-pre-wrap leading-relaxed font-medium">{{ msg?.mensagem }}</p>
              </div>
            </div>
          </template>
        </div>

        <!-- CAMPO DE RESPOSTA -->
        <div class="p-4 sm:p-5 bg-slate-200/50 border-t border-slate-300 shrink-0 z-20">
          <div v-if="ticketSelecionado.status === 'resolvido' || ticketSelecionado.status === 'fechado'" class="text-center p-5 bg-emerald-50 rounded-2xl text-sm text-emerald-800 font-bold border border-emerald-200 shadow-inner">
            🔒 Chamado Encerrado. Agradecemos o contato.
          </div>
          
          <form v-else @submit.prevent="enviarResposta" class="flex flex-col sm:flex-row gap-3 relative">
            <textarea 
              v-model="novaMensagem" 
              rows="2" 
              placeholder="Escreva a sua mensagem para a central..." 
              class="flex-1 border-0 rounded-2xl px-5 py-4 text-sm sm:text-base focus:ring-2 focus:ring-[#035D29] outline-none resize-none bg-white transition-colors shadow-sm font-medium"
              @keydown.enter.prevent="enviarResposta"
            ></textarea>
            <button 
              type="submit" 
              :disabled="enviando || !novaMensagem.trim()" 
              class="px-8 py-4 sm:py-0 bg-[#035D29] text-white rounded-2xl font-black text-sm hover:bg-[#023818] transition-transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center sm:min-w-[150px] shadow-md focus:outline-none"
            >
              <svg v-if="enviando" class="w-5 h-5 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              {{ enviando ? 'Enviando...' : 'Enviar ➔' }}
            </button>
          </form>
        </div>
      </template>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: ABRIR NOVO CHAMADO -->
    <!-- ========================================== -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalNovo" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-[100dvh] pt-4 px-2 sm:px-4 pb-0 text-center sm:p-0">
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showModalNovo = false" aria-hidden="true"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]" aria-hidden="true">&#8203;</span>

          <div class="relative inline-block align-bottom sm:align-middle bg-white rounded-t-3xl sm:rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 w-full sm:max-w-2xl flex flex-col max-h-[90dvh]">
            <div class="bg-white px-6 sm:px-10 pt-8 pb-6 sm:py-8 flex-1 overflow-y-auto scrollbar-clinical">
              <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-5">
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center">
                  <svg class="w-8 h-8 mr-3 text-[#035D29]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                  Novo Chamado
                </h3>
                <button @click="showModalNovo = false" class="text-slate-400 hover:text-slate-700 transition-colors focus:outline-none">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
              </div>

              <div class="space-y-6 sm:space-y-8">
                <div>
                  <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-4">1. Natureza do contato</label>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button v-for="cat in categorias" :key="cat" @click="formNovo.categoria = cat" type="button" 
                            class="p-4 border-2 rounded-2xl text-left transition-all focus:outline-none"
                            :class="formNovo.categoria === cat ? 'border-[#035D29] bg-emerald-50 shadow-md' : 'border-slate-200 bg-white hover:border-slate-300 shadow-sm'">
                      <div class="flex items-center justify-between">
                        <p class="font-bold text-slate-900 text-sm">{{ cat }}</p>
                        <div v-if="formNovo.categoria === cat" class="w-5 h-5 rounded-full bg-[#035D29] flex items-center justify-center shrink-0">
                          <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div v-else class="w-5 h-5 rounded-full border-2 border-slate-300 shrink-0"></div>
                      </div>
                    </button>
                  </div>
                </div>

                <div v-if="formNovo.categoria === 'Disputa de Frete' || formNovo.categoria === 'Problema Operacional'" class="p-5 bg-amber-50 border border-amber-200 rounded-2xl animate-fade-in shadow-inner">
                  <label class="block text-[11px] font-black text-amber-800 uppercase tracking-widest mb-3">2. Carga relacionada <span class="font-bold normal-case opacity-70">(Agiliza o atendimento)</span></label>
                  <input v-model="formNovo.carga_id" type="number" class="w-full sm:w-2/3 border-2 border-amber-200 rounded-xl focus:ring-amber-500 focus:border-amber-500 text-base p-4 bg-white shadow-sm font-bold" placeholder="Digite apenas o ID Numérico (Ex: 1045)">
                </div>

                <div>
                  <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-3">3. Resumo do Assunto</label>
                  <input v-model="formNovo.assunto" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl focus:bg-white focus:ring-[#035D29] focus:border-[#035D29] text-base p-4 shadow-sm font-bold placeholder-slate-400" placeholder="Ex: Embarcador não chegou ao local de coleta">
                </div>

                <div>
                  <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-3">4. Detalhes (O que aconteceu?)</label>
                  <textarea v-model="formNovo.mensagem" rows="4" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl focus:bg-white focus:ring-[#035D29] focus:border-[#035D29] text-base p-5 resize-none shadow-sm font-medium placeholder-slate-400" placeholder="Explique a situação detalhadamente..."></textarea>
                </div>
              </div>
            </div>

            <div class="bg-white px-6 sm:px-10 py-6 border-t border-slate-200 shrink-0">
              <button @click="enviarNovoChamado" :disabled="processandoNovo || !formNovo.categoria || !formNovo.assunto || !formNovo.mensagem" class="w-full px-8 py-5 bg-[#035D29] hover:bg-[#023818] text-white text-lg font-black rounded-2xl shadow-lg transition-transform active:scale-95 disabled:opacity-50 flex items-center justify-center focus:outline-none">
                <svg v-if="processandoNovo" class="w-6 h-6 animate-spin mr-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ processandoNovo ? 'Registrando...' : 'Abrir Chamado Seguro' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

// Estado das Listas
const tickets = ref([]);
const loading = ref(true);

// Estado do Chat
const ticketSelecionado = ref(null);
const ticketDetalhado = ref(null);
const loadingChat = ref(false);
const novaMensagem = ref('');
const enviando = ref(false);

// Estado do Modal (Novo Chamado)
const showModalNovo = ref(false);
const processandoNovo = ref(false);
const categorias = ['Disputa de Frete', 'Problema Operacional', 'Financeiro', 'Dúvida Técnica (Aplicativo)'];
const formNovo = ref({ categoria: '', carga_id: null, assunto: '', mensagem: '' });

const scrollToBottom = async () => {
  await nextTick();
  const container = document.getElementById('chat-container');
  if (container) {
    setTimeout(() => { container.scrollTop = container.scrollHeight; }, 50);
  }
};

// Funções 100% blindadas contra 'undefined'
const formatarStatus = (status) => {
  if (typeof status !== 'string') return 'PROCESSANDO';
  return status.replace(/_/g, ' ');
};

const getStatusBadge = (status) => {
  if (typeof status !== 'string') return 'bg-slate-100 text-slate-800 border-slate-200';
  const map = {
    aberto: 'bg-amber-100 text-amber-800 border-amber-200',
    em_atendimento: 'bg-indigo-100 text-indigo-800 border-indigo-200',
    aguardando_cliente: 'bg-orange-100 text-orange-800 border-orange-200 animate-pulse',
    resolvido: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    fechado: 'bg-slate-200 text-slate-800 border-slate-300'
  };
  return `px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded border ${map[status] || 'bg-slate-100 text-slate-800 border-slate-200'}`;
};

const formatarData = (dataStr) => {
  if (!dataStr) return '--';
  try { return new Date(dataStr).toLocaleDateString('pt-BR'); } catch(e) { return '--'; }
};

const formatarHora = (dataStr) => {
  if (!dataStr) return '--';
  try {
    const d = new Date(dataStr);
    return `${d.toLocaleDateString('pt-BR')} às ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
  } catch(e) { return '--'; }
};

const fetchTickets = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/suporte/tickets');
    let data = res.data;
    
    // DEFESA EXTREMA: Desfaz o erro de "Object.values" do seu Interceptor Global.
    if (Array.isArray(data)) {
      const subArray = data.find(item => Array.isArray(item));
      if (subArray) {
        tickets.value = subArray;
      } else {
        tickets.value = data.filter(item => item && typeof item === 'object' && !Array.isArray(item));
      }
    } else {
      tickets.value = data?.data || [];
    }

  } catch (error) {
    console.error('Erro ao carregar chamados', error);
    tickets.value = [];
  } finally {
    loading.value = false;
  }
};

const enviarNovoChamado = async () => {
  processandoNovo.value = true;
  try {
    const res = await axios.post('/api/v1/suporte/tickets', formNovo.value);
    alert(res.data.message || 'Chamado aberto com sucesso!');
    showModalNovo.value = false;
    formNovo.value = { categoria: '', carga_id: null, assunto: '', mensagem: '' };
    await fetchTickets();
  } catch (error) {
    alert(error.response?.data?.message || 'Erro ao abrir chamado.');
  } finally {
    processandoNovo.value = false;
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
    console.error('Erro ao carregar detalhes', error);
  } finally {
    loadingChat.value = false;
  }
};

const enviarResposta = async () => {
  if (!novaMensagem.value.trim() || !ticketSelecionado.value?.id || enviando.value) return;
  
  enviando.value = true;
  try {
    await axios.post(`/api/v1/suporte/tickets/${ticketSelecionado.value.id}/mensagens`, {
      mensagem: novaMensagem.value
    });
    
    novaMensagem.value = '';
    await abrirTicket(ticketSelecionado.value);
    
    // Atualização silenciosa e blindada
    axios.get('/api/v1/suporte/tickets').then(res => {
      let data = res.data;
      let novaLista = [];
      if (Array.isArray(data)) {
        const subArray = data.find(item => Array.isArray(item));
        novaLista = subArray ? subArray : data.filter(item => item && typeof item === 'object' && !Array.isArray(item));
      } else {
        novaLista = data?.data || [];
      }
      tickets.value = novaLista;
      const t = tickets.value.find(item => item.id === ticketSelecionado.value.id);
      if(t) ticketSelecionado.value = t;
    }).catch(e => console.error(e));

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

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px; }
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>