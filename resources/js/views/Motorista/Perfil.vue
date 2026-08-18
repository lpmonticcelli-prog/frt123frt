<template>
  <div class="animate-fade-in space-y-4 sm:space-y-6 relative pb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      
      <!-- HEADER DO PAINEL -->
      <div class="px-4 py-5 sm:px-6 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Minha Conta & Documentação</h3>
          <p class="text-sm text-slate-500 mt-1 font-medium">Gerencie seus dados e mantenha sua documentação em dia para receber fretes.</p>
        </div>
      </div>

      <!-- ESTADO: CARREGANDO -->
      <div v-if="loading" class="p-12 text-center text-slate-500 font-medium flex flex-col items-center">
        <svg class="w-10 h-10 animate-spin mb-4 text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="text-sm">Sincronizando seus dados...</span>
      </div>

      <!-- CONTEÚDO PRINCIPAL -->
      <div v-else class="p-4 sm:p-6 lg:p-8">
        
        <!-- Status da Conta 123Fretei -->
        <div :class="['p-5 rounded-xl mb-6 border shadow-inner flex flex-col', getStatusAlertClass(perfil.status_verificacao)]">
          <div class="flex items-center">
            <svg v-if="perfil.status_verificacao === 'aprovado'" class="w-6 h-6 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <svg v-else-if="perfil.status_verificacao === 'pendente'" class="w-6 h-6 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <svg v-else class="w-6 h-6 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="font-black text-lg sm:text-xl capitalize tracking-tight">Status da Conta: {{ perfil.status_verificacao?.replace('_', ' ') || 'Pendente' }}</div>
          </div>
          <p class="mt-2 text-sm font-medium">{{ getStatusMensagem(perfil.status_verificacao) }}</p>
        </div>

        <!-- GRID DE DADOS PESSOAIS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 bg-slate-50 p-5 rounded-2xl border border-slate-200 shadow-inner">
          <div class="min-w-0">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Nome Completo</label>
            <div class="text-sm font-bold text-slate-900 truncate" :title="perfil.nome">{{ perfil.nome }}</div>
          </div>
          <div class="min-w-0">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">E-mail</label>
            <div class="text-sm font-bold text-slate-900 truncate" :title="perfil.email">{{ perfil.email }}</div>
          </div>
          <div class="min-w-0">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Telefone</label>
            <div class="text-sm font-bold text-slate-900 truncate">{{ perfil.telefone }}</div>
          </div>
          <div class="min-w-0">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">CPF</label>
            <div class="text-sm font-bold text-slate-900 truncate">{{ perfil.cpf }}</div>
          </div>
        </div>

        <!-- SESSÃO: GERENCIADORA DE RISCO (GR) -->
        <div class="border-t border-slate-200 pt-8 mb-8">
          <h4 class="text-xl font-black text-slate-900 mb-2 tracking-tight">Gerenciadora de Risco (Trans Sat)</h4>
          <p class="text-sm text-slate-500 mb-6 font-medium leading-relaxed max-w-3xl">Para operar em nossa malha e receber os lances de frete, o seu perfil e veículos cadastrados precisam de liberação ativa na GR oficial.</p>

          <div :class="['p-5 rounded-xl mb-6 border shadow-inner flex flex-col', getGrStatusAlertClass(perfil.gr_status)]">
            <div class="flex items-center">
              <svg class="w-6 h-6 mr-2 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
              <div class="font-black text-lg sm:text-xl capitalize tracking-tight">Status GR: {{ perfil.gr_status?.replace('_', ' ') || 'Não Solicitado' }}</div>
            </div>
            <p class="mt-2 text-sm font-medium">{{ getGrStatusMensagem(perfil.gr_status) }}</p>
          </div>

          <!-- AÇÕES DA GR -->
          <div class="flex flex-col sm:flex-row gap-3 justify-start">
            
            <button 
              v-if="['nao_solicitado', 'rejeitado'].includes(perfil.gr_status)" 
              @click="solicitarAnaliseGr" 
              :disabled="!canClickGrButton" 
              :class="[
                'w-full sm:w-auto px-6 py-3.5 sm:py-3 rounded-xl text-sm text-white font-bold transition-colors shadow-md flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900',
                canClickGrButton ? 'bg-slate-900 hover:bg-slate-800' : 'bg-slate-400 cursor-not-allowed opacity-80'
              ]">
              <svg v-if="grActionLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ grButtonText }}
            </button>

            <button 
              v-if="perfil.gr_status === 'aguardando_biometria'" 
              @click="showBiometriaModal = true" 
              class="w-full sm:w-auto px-6 py-3.5 sm:py-3 rounded-xl text-indigo-800 bg-indigo-100 border border-indigo-300 font-bold hover:bg-indigo-200 transition-colors shadow-sm flex items-center justify-center focus:outline-none text-sm">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
              Realizar Prova de Vida
            </button>
          </div>
        </div>

        <!-- SESSÃO: ENVIO DE DOCUMENTOS (KYC) -->
        <div class="border-t border-slate-200 pt-8 mt-8">
          <h4 class="text-xl font-black text-slate-900 mb-2 tracking-tight">Envio de Documentos (KYC Interno)</h4>
          <p class="text-sm text-slate-500 mb-6 font-medium leading-relaxed max-w-3xl">Envie fotos legíveis ou arquivos em PDF. As imagens serão comprimidas no seu celular para economizar pacote de dados. <strong class="text-rose-500">Qualquer envio altera o status da sua conta para "Em Análise".</strong></p>

          <form @submit.prevent="submitDocumentos" class="space-y-4 sm:space-y-6">
            
            <div class="bg-white p-4 sm:p-5 border border-slate-200 rounded-xl shadow-sm transition-all focus-within:ring-2 focus-within:ring-[#035D29]/20 focus-within:border-[#035D29]">
              <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-1">Foto da CNH</label>
              <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'cnh')" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-bold hover:file:bg-slate-200 transition-colors cursor-pointer outline-none" />
                <a v-if="perfil.doc_cnh_url" :href="perfil.doc_cnh_url" target="_blank" class="text-xs font-bold text-[#035D29] bg-emerald-50 px-3 py-1.5 rounded-md border border-emerald-200 self-start sm:self-auto hover:bg-emerald-100 transition-colors flex items-center shrink-0">Ver Arquivo Atual</a>
              </div>
            </div>

            <!-- PROVA DE VIDA CNH -->
            <div class="bg-amber-50 p-4 sm:p-5 border border-amber-200 rounded-xl shadow-sm transition-all focus-within:ring-2 focus-within:ring-amber-500/20">
              <label class="block text-xs font-black text-amber-900 uppercase tracking-widest mb-1">Prova de Vida (Selfie segurando a CNH)</label>
              <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <input type="file" accept="image/*" capture="user" @change="(e) => handleFileUpload(e, 'selfie_cnh')" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-amber-100 file:text-amber-800 file:font-bold hover:file:bg-amber-200 transition-colors cursor-pointer outline-none" />
                <a v-if="perfil.doc_selfie_cnh_url" :href="perfil.doc_selfie_cnh_url" target="_blank" class="text-xs font-bold text-amber-700 bg-amber-100 px-3 py-1.5 rounded-md border border-amber-300 self-start sm:self-auto hover:bg-amber-200 transition-colors flex items-center shrink-0">Ver Arquivo Atual</a>
              </div>
            </div>

            <div class="bg-white p-4 sm:p-5 border border-slate-200 rounded-xl shadow-sm transition-all focus-within:ring-2 focus-within:ring-[#035D29]/20 focus-within:border-[#035D29]">
              <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-1">Documento RNTRC</label>
              <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'rntrc')" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-bold hover:file:bg-slate-200 transition-colors cursor-pointer outline-none" />
                <a v-if="perfil.doc_rntrc_url" :href="perfil.doc_rntrc_url" target="_blank" class="text-xs font-bold text-[#035D29] bg-emerald-50 px-3 py-1.5 rounded-md border border-emerald-200 self-start sm:self-auto hover:bg-emerald-100 transition-colors flex items-center shrink-0">Ver Arquivo Atual</a>
              </div>
            </div>

            <div class="bg-white p-4 sm:p-5 border border-slate-200 rounded-xl shadow-sm transition-all focus-within:ring-2 focus-within:ring-[#035D29]/20 focus-within:border-[#035D29]">
              <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-1">Comprovante de Endereço</label>
              <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'endereco')" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-bold hover:file:bg-slate-200 transition-colors cursor-pointer outline-none" />
                <a v-if="perfil.doc_comprovante_endereco_url" :href="perfil.doc_comprovante_endereco_url" target="_blank" class="text-xs font-bold text-[#035D29] bg-emerald-50 px-3 py-1.5 rounded-md border border-emerald-200 self-start sm:self-auto hover:bg-emerald-100 transition-colors flex items-center shrink-0">Ver Arquivo Atual</a>
              </div>
            </div>

            <!-- Botão Salvar Documentos -->
            <div class="flex justify-end pt-6 border-t border-slate-100 mt-6">
              <button type="submit" :disabled="!hasArquivosSelecionados || actionLoading" class="w-full sm:w-auto px-8 py-3.5 sm:py-3 rounded-xl text-white text-sm font-bold bg-[#035D29] hover:bg-[#023818] disabled:opacity-50 transition-colors shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#035D29] flex justify-center items-center">
                <svg v-if="actionLoading" class="animate-spin h-4 w-4 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ actionLoading ? 'Enviando ao Servidor...' : 'Salvar Documentos' }}
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>

    <!-- MODAL DE BIOMETRIA (BLINDADO) -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showBiometriaModal" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:p-0">
          
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showBiometriaModal = false" aria-hidden="true"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]" aria-hidden="true">&#8203;</span>
          
          <div class="relative inline-block align-bottom sm:align-middle bg-white rounded-t-2xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 w-full sm:w-11/12 sm:max-w-md flex flex-col max-h-[90dvh]">
            
            <div class="px-6 py-5 bg-slate-900 flex justify-between items-center shrink-0">
              <h3 class="text-lg font-black text-white tracking-tight">Validação Facial</h3>
              <button @click="showBiometriaModal = false" class="text-slate-400 hover:text-white transition-colors focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </div>
            
            <div class="p-6 text-center overflow-y-auto scrollbar-clinical flex-1 bg-white">
              <p class="text-sm font-medium text-slate-600 mb-6 leading-relaxed">Aponte a câmera do seu celular para o QR Code abaixo para realizar a prova de vida da Trans Sat. O processo é rápido e blindado.</p>
              
              <div class="flex justify-center mb-8">
                <div class="p-3 bg-white border border-slate-200 rounded-2xl shadow-sm">
                  <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(linkBiometria)}`" alt="QR Code Biometria" class="w-48 h-48 sm:w-56 sm:h-56 object-contain" />
                </div>
              </div>

              <div class="relative flex py-2 items-center mb-6">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink-0 mx-4 text-slate-400 text-[10px] font-black uppercase tracking-widest">Ou copie o link direto</span>
                <div class="flex-grow border-t border-slate-200"></div>
              </div>

              <div class="flex flex-col sm:flex-row mt-2 rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="relative flex-grow focus-within:z-10 bg-slate-50">
                  <input type="text" readonly :value="linkBiometria" class="block w-full border-none bg-transparent text-sm py-3.5 sm:py-3 px-4 text-slate-700 outline-none font-mono truncate" />
                </div>
                <button @click="copiarLinkBiometria" :class="['w-full sm:w-auto relative inline-flex justify-center items-center px-6 py-3.5 sm:py-3 text-sm font-bold focus:outline-none transition-colors', copiado ? 'bg-[#035D29] text-white' : 'bg-slate-200 text-slate-800 hover:bg-slate-300']">
                  <svg v-if="!copiado" class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                  <svg v-else class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  <span>{{ copiado ? 'Copiado!' : 'Copiar' }}</span>
                </button>
              </div>
            </div>
            
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import imageCompression from 'browser-image-compression';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const perfil = ref({});
const loading = ref(true);
const actionLoading = ref(false);

// GR Estados e Lógica de Cooldown
const grActionLoading = ref(false);
const grCooldownSeconds = ref(0);
const showBiometriaModal = ref(false);
const copiado = ref(false);

// Texto Dinâmico do Botão
const grButtonText = computed(() => {
    if (grActionLoading.value) return 'Processando...';
    if (grCooldownSeconds.value > 0) return `Aguarde ${grCooldownSeconds.value}s`;
    return 'Solicitar Análise na GR';
});

// Bloqueia o clique durante processamento ou cooldown
const canClickGrButton = computed(() => {
    return !grActionLoading.value && grCooldownSeconds.value === 0;
});

// Link computado
const linkBiometria = computed(() => {
  return perfil.value.gr_biometria_url || 'https://gr.app.br/validacao/f/empresa/codigo_hash';
});

const arquivos = ref({ cnh: null, selfie_cnh: null, rntrc: null, endereco: null });

const hasArquivosSelecionados = computed(() => arquivos.value.cnh || arquivos.value.selfie_cnh || arquivos.value.rntrc || arquivos.value.endereco);

const getStatusAlertClass = (status) => {
  const classes = { 
    pendente: 'bg-amber-50 border-amber-200 text-amber-800', 
    em_analise: 'bg-indigo-50 border-indigo-200 text-indigo-800', 
    aprovado: 'bg-emerald-50 border-emerald-200 text-emerald-800', 
    rejeitado: 'bg-rose-50 border-rose-200 text-rose-800' 
  };
  return classes[status] || 'bg-slate-50 border-slate-200 text-slate-800';
};

const getStatusMensagem = (status) => {
  const msgs = { 
    pendente: 'Você precisa enviar seus documentos obrigatórios.', 
    em_analise: 'Seus documentos estão na fila de auditoria da plataforma.', 
    aprovado: 'Auditoria interna concluída. Dados aprovados!', 
    rejeitado: 'Houve um problema na auditoria. Reenvie arquivos legíveis.' 
  };
  return msgs[status] || '';
};

const getGrStatusAlertClass = (status) => {
  const classes = {
    nao_solicitado: 'bg-slate-50 border-slate-200 text-slate-800',
    pendente: 'bg-indigo-50 border-indigo-200 text-indigo-800',
    aprovado: 'bg-emerald-50 border-emerald-200 text-emerald-800',
    rejeitado: 'bg-rose-50 border-rose-200 text-rose-800',
    aguardando_biometria: 'bg-amber-50 border-amber-200 text-amber-800'
  };
  return classes[status] || 'bg-slate-50 border-slate-200 text-slate-800';
};

const getGrStatusMensagem = (status) => {
  const msgs = {
    nao_solicitado: 'Clique no botão abaixo para submeter o seu perfil à Gerenciadora de Risco.',
    pendente: 'Os seus dados estão sendo processados pela GR. Este processo pode levar alguns minutos.',
    aprovado: 'Autorizado pela GR! Você está com a permissão máxima para aceitar cargas.',
    rejeitado: 'O seu perfil ou veículo apresentou restrições ou divergências na base de dados.',
    aguardando_biometria: 'Atenção! A GR exige a sua biometria facial para liberar o perfil. Clique no botão abaixo para concluir.'
  };
  return msgs[status] || '';
};

const fetchPerfil = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/motorista/perfil');
    perfil.value = response.data;
  } catch (error) { alert('Erro ao carregar perfil.'); } finally { loading.value = false; }
};

// ========================================================
// LÓGICA DO BOTÃO INTELIGENTE
// ========================================================
const iniciarCooldown = (segundos) => {
    grCooldownSeconds.value = segundos;
    const interval = setInterval(() => {
        grCooldownSeconds.value--;
        if (grCooldownSeconds.value <= 0) {
            clearInterval(interval);
        }
    }, 1000);
};

const solicitarAnaliseGr = async () => {
  if (!canClickGrButton.value) return;
  
  grActionLoading.value = true;
  
  try {
    const response = await axios.post('/api/v1/motorista/perfil/gr/solicitar');
    alert(response.data.message);
    await fetchPerfil();
  } catch (error) {
    if (error.response && error.response.status === 429) {
        alert("Muitas tentativas simultâneas. Por favor, aguarde o contador terminar.");
    } else {
        alert(error.response?.data?.error || 'Erro ao comunicar com a Gerenciadora de Risco.');
    }
  } finally {
    grActionLoading.value = false;
    iniciarCooldown(60); // Trava o botão visualmente por 60s
  }
};
// ========================================================

const copiarLinkBiometria = async () => {
  try {
    await navigator.clipboard.writeText(linkBiometria.value);
    copiado.value = true;
    setTimeout(() => { copiado.value = false; }, 2000);
  } catch (err) {
    alert('Erro ao copiar o link. Por favor, selecione e copie manualmente.');
  }
};

const handleFileUpload = async (event, tipo) => {
  const file = event.target.files[0];
  if (!file) return;
  if (file.type === 'application/pdf') { arquivos.value[tipo] = file; return; }

  try {
    const compressedBlob = await imageCompression(file, { maxSizeMB: 1, maxWidthOrHeight: 1600 });
    arquivos.value[tipo] = new File([compressedBlob], file.name, { type: compressedBlob.type });
  } catch (error) { arquivos.value[tipo] = file; }
};

const submitDocumentos = async () => {
  actionLoading.value = true;
  const formData = new FormData();
  if (arquivos.value.cnh) formData.append('doc_cnh', arquivos.value.cnh);
  if (arquivos.value.selfie_cnh) formData.append('doc_selfie_cnh', arquivos.value.selfie_cnh);
  if (arquivos.value.rntrc) formData.append('doc_rntrc', arquivos.value.rntrc);
  if (arquivos.value.endereco) formData.append('doc_comprovante_endereco', arquivos.value.endereco);

  try {
    const response = await axios.post('/api/v1/motorista/perfil', formData, { headers: { 'Content-Type': 'multipart/form-data' }});
    alert(response.data.message);
    await fetchPerfil();
    await authStore.fetchUser(); 
    arquivos.value = { cnh: null, selfie_cnh: null, rntrc: null, endereco: null };
  } catch (error) { alert(error.response?.data?.message || 'Erro ao enviar documentos.'); } finally { actionLoading.value = false; }
};

onMounted(() => fetchPerfil());
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Scrollbars Clean */
.scrollbar-clinical::-webkit-scrollbar { width: 5px; height: 5px;}
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>