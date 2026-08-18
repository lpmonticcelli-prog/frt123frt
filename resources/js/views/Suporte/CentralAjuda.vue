<template>
  <div class="space-y-4 sm:space-y-6 relative pb-8 animate-fade-in px-2 sm:px-0">
    
    <!-- HEADER DA CENTRAL -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5 sm:p-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-5">
      <div>
        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Central de Ajuda</h2>
        <p class="text-sm sm:text-base text-slate-500 mt-1 font-medium">Como podemos ajudar na sua operação hoje?</p>
      </div>
      <div class="w-full sm:w-auto shrink-0">
        <button v-if="view !== 'novo'" @click="iniciarNovoChamado" class="w-full sm:w-auto px-6 py-4 sm:py-3.5 bg-[#035D29] hover:bg-[#023818] text-white text-base font-black rounded-2xl shadow-lg transition-transform active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#035D29]/50 flex justify-center items-center">
          <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
          Abrir Novo Chamado
        </button>
        <button v-else @click="view = 'lista'" class="w-full sm:w-auto px-6 py-4 sm:py-3.5 bg-white border-2 border-slate-200 hover:bg-slate-50 text-slate-700 text-base font-black rounded-2xl shadow-sm transition-transform active:scale-95 focus:outline-none flex justify-center items-center">
          <svg class="w-6 h-6 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          Voltar para Lista
        </button>
      </div>
    </div>

    <!-- VIEW 1: LISTA DE CHAMADOS -->
    <div v-if="view === 'lista'" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden animate-fade-in">
      
      <!-- LOADING -->
      <div v-if="loading" class="flex flex-col items-center justify-center p-16 text-slate-500 font-bold">
        <svg class="w-12 h-12 animate-spin mb-4 text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="text-base tracking-wide">Buscando seu histórico...</span>
      </div>
      
      <div v-else>
        <!-- MOBILE VIEW (CARDS) -->
        <div class="block lg:hidden divide-y divide-slate-100">
          <div v-if="!meusTickets || meusTickets.length === 0" class="px-6 py-16 text-center text-slate-500 font-medium text-base bg-slate-50">
            Você não tem nenhum chamado aberto na central.
          </div>
          
          <div v-for="ticket in (meusTickets || [])" :key="`mob-${ticket.id}`" class="p-6 hover:bg-slate-50 transition-colors">
            <div class="flex justify-between items-start mb-4 gap-2">
              <span :class="getStatusBadge(ticket.status)">{{ ticket.status?.replace('_', ' ') }}</span>
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest shrink-0 mt-1">{{ formatarData(ticket.created_at) }}</span>
            </div>
            
            <h4 class="text-lg font-black text-slate-900 leading-tight mb-4" :title="ticket.assunto">#{{ ticket.id }} - {{ ticket.assunto }}</h4>
            
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 flex flex-col gap-3 mb-5 shadow-inner">
              <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500 font-black uppercase tracking-widest text-[10px]">Categoria</span>
                <span class="font-bold text-slate-800 text-right">{{ ticket.categoria }}</span>
              </div>
              <div v-if="ticket.carga_id" class="flex justify-between items-center text-sm border-t border-slate-200 pt-3">
                <span class="text-slate-500 font-black uppercase tracking-widest text-[10px]">Frete Relacionado</span>
                <span class="font-black text-[#035D29] bg-emerald-50 px-2 py-1 rounded border border-emerald-100">Carga #{{ ticket.carga_id }}</span>
              </div>
            </div>

            <button @click="abrirDetalhes(ticket.id)" class="w-full py-4 sm:py-3.5 bg-white border-2 border-slate-200 text-slate-700 hover:bg-slate-50 font-black rounded-2xl shadow-sm focus:outline-none transition-colors text-base flex justify-center items-center">
              Acompanhar Interação <span class="ml-2">➔</span>
            </button>
          </div>
        </div>

        <!-- DESKTOP VIEW (TABELA CLEAN) -->
        <div class="hidden lg:block w-full overflow-x-auto scrollbar-clinical">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-5 text-left text-[11px] font-black text-slate-500 uppercase tracking-widest">Chamado de Suporte</th>
                <th class="px-6 py-5 text-left text-[11px] font-black text-slate-500 uppercase tracking-widest">Classificação</th>
                <th class="px-6 py-5 text-center text-[11px] font-black text-slate-500 uppercase tracking-widest">Status</th>
                <th class="px-6 py-5 text-right text-[11px] font-black text-slate-500 uppercase tracking-widest">Ação</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-if="!meusTickets || meusTickets.length === 0">
                <td colspan="4" class="px-6 py-16 text-center text-slate-500 font-medium text-base">Você não tem nenhum chamado aberto.</td>
              </tr>
              <tr v-for="ticket in (meusTickets || [])" :key="`desk-${ticket.id}`" class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-5">
                  <div class="text-base font-black text-slate-900 mb-1 max-w-[300px] truncate" :title="ticket.assunto">#{{ ticket.id }} - {{ ticket.assunto }}</div>
                  <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Aberto em {{ formatarData(ticket.created_at) }}
                  </div>
                </td>
                <td class="px-6 py-5">
                  <span class="text-sm font-bold text-slate-700 block">{{ ticket.categoria }}</span>
                  <div v-if="ticket.carga_id" class="text-[10px] font-black text-[#035D29] mt-1.5 uppercase tracking-widest bg-emerald-50 inline-block px-2 py-0.5 rounded border border-emerald-200">Carga #{{ ticket.carga_id }}</div>
                </td>
                <td class="px-6 py-5 text-center">
                  <span :class="getStatusBadge(ticket.status)">{{ ticket.status?.replace('_', ' ') }}</span>
                </td>
                <td class="px-6 py-5 text-right">
                  <button @click="abrirDetalhes(ticket.id)" class="inline-flex items-center px-6 py-3 border-2 border-slate-200 text-slate-700 bg-white hover:bg-slate-50 font-black rounded-xl shadow-sm focus:outline-none transition-colors text-sm">
                    Acompanhar <span class="ml-2 font-normal">➔</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- VIEW 2: ABRIR NOVO CHAMADO -->
    <div v-if="view === 'novo'" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 sm:p-10 animate-fade-in max-w-3xl mx-auto">
      <h3 class="text-2xl font-black text-slate-900 mb-8 border-b border-slate-100 pb-5 tracking-tight flex items-center">
        <svg class="w-8 h-8 mr-3 text-[#035D29]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
        Descreva o problema
      </h3>
      
      <div class="space-y-8">
        <div>
          <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-4">1. Qual a natureza do contato?</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button v-for="cat in categorias" :key="cat" @click="formNovo.categoria = cat" type="button" 
                    class="p-5 border-2 rounded-2xl text-left transition-all focus:outline-none"
                    :class="formNovo.categoria === cat ? 'border-[#035D29] bg-emerald-50 shadow-md' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50 shadow-sm'">
              <div class="flex items-center justify-between">
                <p class="font-bold text-slate-900 text-sm sm:text-base">{{ cat }}</p>
                <div v-if="formNovo.categoria === cat" class="w-5 h-5 rounded-full bg-[#035D29] flex items-center justify-center shrink-0">
                  <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div v-else class="w-5 h-5 rounded-full border-2 border-slate-300 shrink-0"></div>
              </div>
            </button>
          </div>
        </div>

        <div v-if="formNovo.categoria === 'Disputa de Frete' || formNovo.categoria === 'Problema Operacional'" class="p-6 bg-amber-50 border border-amber-200 rounded-2xl animate-fade-in shadow-inner">
          <label class="block text-[11px] font-black text-amber-800 uppercase tracking-widest mb-3">2. Carga relacionada <span class="font-bold normal-case opacity-70">(Agiliza o atendimento)</span></label>
          <input v-model="formNovo.carga_id" type="number" class="w-full sm:w-2/3 border-2 border-amber-200 rounded-xl focus:ring-amber-500 focus:border-amber-500 text-base p-4 bg-white shadow-sm font-bold" placeholder="Digite apenas o ID Numérico (Ex: 1045)">
        </div>

        <div>
          <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-3">3. Resumo do Assunto</label>
          <input v-model="formNovo.assunto" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl focus:bg-white focus:ring-[#035D29] focus:border-[#035D29] text-base p-4 shadow-sm font-bold placeholder-slate-400" placeholder="Ex: Embarcador não chegou ao local de coleta">
        </div>

        <div>
          <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-3">4. Detalhes (O que aconteceu?)</label>
          <textarea v-model="formNovo.mensagem" rows="5" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl focus:bg-white focus:ring-[#035D29] focus:border-[#035D29] text-base p-5 resize-none shadow-sm font-medium placeholder-slate-400" placeholder="Explique a situação detalhadamente..."></textarea>
        </div>

        <div class="pt-6 border-t border-slate-100 flex justify-end">
          <button @click="enviarNovoChamado" :disabled="processando || !formNovo.categoria || !formNovo.assunto || !formNovo.mensagem" class="w-full sm:w-auto px-10 py-5 sm:py-4 bg-[#035D29] hover:bg-[#023818] text-white text-lg font-black rounded-2xl shadow-lg transition-transform active:scale-95 disabled:opacity-50 flex items-center justify-center focus:outline-none">
            <svg v-if="processando" class="w-6 h-6 animate-spin mr-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            {{ processando ? 'Registrando...' : 'Abrir Chamado Seguro' }}
          </button>
        </div>
      </div>
    </div>

    <!-- VIEW 3: DETALHE DO CHAMADO (CHAT ESTILO WHATSAPP) -->
    <div v-if="view === 'detalhe' && ticketAtivo" class="bg-slate-100 rounded-3xl shadow-md border border-slate-200 flex flex-col h-[80vh] sm:h-[700px] animate-fade-in max-w-4xl mx-auto overflow-hidden">
      
      <!-- HEADER DO CHAT -->
      <div class="p-5 border-b border-slate-800 bg-slate-900 flex flex-col sm:flex-row justify-between items-start sm:items-center shrink-0 gap-4 shadow-md z-10">
        <div class="flex items-center gap-4 w-full sm:w-auto">
          <button @click="view = 'lista'" class="text-slate-400 hover:text-white transition-colors focus:outline-none p-1">
            <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          </button>
          <div class="flex-1 min-w-0">
            <h3 class="font-black text-white text-lg sm:text-xl truncate tracking-tight" :title="ticketAtivo.assunto">#{{ ticketAtivo.id }} - {{ ticketAtivo.assunto }}</h3>
            <p class="text-[10px] sm:text-xs text-emerald-400 font-bold uppercase tracking-widest mt-0.5">Suporte Operacional Ativo</p>
          </div>
        </div>
        <span :class="getStatusBadge(ticketAtivo.status)">{{ ticketAtivo.status?.replace('_', ' ') }}</span>
      </div>

      <!-- ÁREA DE MENSAGENS (COM FUNDO PADRÃO) -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 scrollbar-clinical relative" id="chat-messages">
        <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 20px 20px;"></div>
        
        <div v-for="msg in ticketAtivo.messages" :key="msg.id" class="flex flex-col w-full relative z-10" :class="msg.user_id === ticketAtivo.user_id ? 'items-end' : 'items-start'">
          <div class="max-w-[85%] sm:max-w-[70%] rounded-2xl px-5 py-4 shadow-sm" :class="msg.user_id === ticketAtivo.user_id ? 'bg-[#035D29] text-white rounded-br-none' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-none'">
            <div class="flex justify-between items-end mb-2 space-x-4">
              <span class="text-[10px] font-black uppercase tracking-widest" :class="msg.user_id === ticketAtivo.user_id ? 'text-emerald-300' : 'text-[#ff5500]'">
                {{ msg.user_id === ticketAtivo.user_id ? 'Você' : 'Central 123fretei' }}
              </span>
              <span class="text-[9px] font-bold opacity-60 tabular-nums">{{ formatarDataHora(msg.created_at) }}</span>
            </div>
            <p class="text-sm sm:text-base whitespace-pre-wrap leading-relaxed font-medium">{{ msg.mensagem }}</p>
          </div>
        </div>
      </div>

      <!-- ÁREA DE RESPOSTA -->
      <div v-if="ticketAtivo.status !== 'resolvido' && ticketAtivo.status !== 'fechado'" class="p-4 sm:p-5 bg-slate-200/50 border-t border-slate-300 shrink-0">
        <form @submit.prevent="enviarResposta" class="flex flex-col sm:flex-row gap-3 relative">
          <textarea 
            v-model="novaMensagem" 
            rows="2" 
            placeholder="Escreva a sua resposta..." 
            class="flex-1 border-0 rounded-2xl px-5 py-4 text-base focus:ring-2 focus:ring-[#035D29] outline-none resize-none bg-white shadow-sm font-medium"
            @keydown.enter.prevent="enviarResposta"
          ></textarea>
          <button 
            type="submit" 
            :disabled="processando || !novaMensagem.trim()" 
            class="px-8 py-4 sm:py-0 bg-[#035D29] hover:bg-[#023818] text-white font-black rounded-2xl transition-transform active:scale-95 disabled:opacity-50 flex items-center justify-center sm:min-w-[160px] shadow-md focus:outline-none text-lg"
          >
            <span v-if="!processando">Enviar ➔</span>
            <svg v-else class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          </button>
        </form>
      </div>
      
      <div v-else class="p-6 bg-emerald-50 text-center flex flex-col items-center justify-center text-emerald-800 border-t border-emerald-200 shrink-0">
        <svg class="w-8 h-8 mb-2 text-[#035D29]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-black text-base tracking-wide uppercase">Chamado Encerrado</span>
        <span class="text-sm font-medium mt-1">Agradecemos o seu contato com a equipe de operações.</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
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

// Utilitários Visuais Modernizados
const getStatusBadge = (status) => {
  const map = {
    aberto: 'bg-amber-100 text-amber-800 border-amber-200',
    em_atendimento: 'bg-indigo-100 text-indigo-800 border-indigo-200',
    aguardando_cliente: 'bg-orange-100 text-orange-800 border-orange-200 animate-pulse',
    resolvido: 'bg-emerald-100 text-emerald-800 border-emerald-200'
  };
  return `px-3.5 py-1.5 text-[10px] sm:text-xs font-black rounded-lg uppercase tracking-widest border ${map[status] || 'bg-slate-100 text-slate-800 border-slate-200'}`;
};

const formatarData = (dataStr) => {
  if (!dataStr) return '--';
  try { return new Date(dataStr).toLocaleDateString('pt-BR'); } catch(e) { return '--'; }
};

const formatarDataHora = (dataStr) => {
  if (!dataStr) return '--';
  try {
    const d = new Date(dataStr);
    return `${d.toLocaleDateString('pt-BR')} às ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
  } catch(e) { return '--'; }
};

// API Calls
const carregarMeusTickets = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/suporte/tickets');
    // Blindagem de array
    meusTickets.value = Array.isArray(res.data) ? res.data : [];
  } catch (error) {
    console.error('Erro ao carregar tickets', error);
    meusTickets.value = [];
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
    const res = await axios.post('/api/v1/suporte/tickets', formNovo.value);
    alert(res.data.message || 'Chamado aberto com sucesso!');
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
    const res = await axios.get(`/api/v1/suporte/tickets/${id}`);
    ticketAtivo.value = res.data;
    view.value = 'detalhe';
    await nextTick();
    
    // Auto-scroll suave para a última mensagem
    const chatContainer = document.getElementById('chat-messages');
    if(chatContainer) {
      setTimeout(() => { chatContainer.scrollTop = chatContainer.scrollHeight; }, 100);
    }
  } catch (error) {
    alert('Erro ao carregar histórico da conversa.');
  } finally {
    loading.value = false;
  }
};

const enviarResposta = async () => {
  if (!novaMensagem.value.trim() || processando.value) return;
  processando.value = true;
  
  try {
    // BUG FIX: Endpoint correto de resposta do usuário é /mensagens (conforme routes/api.php)
    await axios.post(`/api/v1/suporte/tickets/${ticketAtivo.value.id}/mensagens`, {
      mensagem: novaMensagem.value
    });
    
    novaMensagem.value = '';
    await abrirDetalhes(ticketAtivo.value.id); // Recarrega o chat
    
    // Atualiza o status na lista localmente para refletir uma nova interação
    const ticketNaLista = meusTickets.value.find(t => t.id === ticketAtivo.value.id);
    if(ticketNaLista) ticketNaLista.status = ticketAtivo.value.status;
    
  } catch (error) {
    alert('Erro ao enviar mensagem. Tente novamente.');
  } finally {
    processando.value = false;
  }
};

onMounted(carregarMeusTickets);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Scrollbars Clean para o Chat */
.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px;}
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>