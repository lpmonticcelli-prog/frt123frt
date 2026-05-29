<template>
  <div class="space-y-6 relative">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-bold text-gray-800">Minha Conta & Documentação</h3>
        <p class="text-sm text-gray-500">Gerencie seus dados e mantenha sua documentação em dia para receber fretes.</p>
      </div>

      <div v-if="loading" class="p-8 text-center text-gray-500 font-medium">Carregando seus dados...</div>

      <div v-else class="p-6">
        <div :class="['p-4 rounded-md mb-6 border', getStatusAlertClass(perfil.status_verificacao)]">
          <div class="flex items-center">
            <div class="font-bold text-lg capitalize mr-2">Status da Conta: {{ perfil.status_verificacao?.replace('_', ' ') || 'Pendente' }}</div>
          </div>
          <p class="mt-1 text-sm">{{ getStatusMensagem(perfil.status_verificacao) }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 bg-gray-50 p-4 rounded-lg border border-gray-100">
          <div><label class="block text-xs font-bold text-gray-500 uppercase">Nome Completo</label><div class="mt-1 text-sm font-medium text-gray-900">{{ perfil.nome }}</div></div>
          <div><label class="block text-xs font-bold text-gray-500 uppercase">E-mail</label><div class="mt-1 text-sm font-medium text-gray-900">{{ perfil.email }}</div></div>
          <div><label class="block text-xs font-bold text-gray-500 uppercase">Telefone</label><div class="mt-1 text-sm font-medium text-gray-900">{{ perfil.telefone }}</div></div>
          <div><label class="block text-xs font-bold text-gray-500 uppercase">CPF</label><div class="mt-1 text-sm font-medium text-gray-900">{{ perfil.cpf }}</div></div>
        </div>

        <div class="border-t border-gray-200 pt-6 mb-8">
          <h4 class="text-md font-bold text-gray-800 mb-4">Gerenciadora de Risco (Trans Sat)</h4>
          <p class="text-sm text-gray-500 mb-6">Para aceitar fretes na plataforma, o seu perfil e veículos cadastrados precisam ser aprovados pela GR.</p>

          <div :class="['p-4 rounded-md mb-6 border', getGrStatusAlertClass(perfil.gr_status)]">
            <div class="flex items-center">
              <div class="font-bold text-lg capitalize mr-2">Status GR: {{ perfil.gr_status?.replace('_', ' ') || 'Não Solicitado' }}</div>
            </div>
            <p class="mt-1 text-sm font-medium">{{ getGrStatusMensagem(perfil.gr_status) }}</p>
          </div>

          <div class="flex flex-col sm:flex-row gap-4 justify-start">
            
            <button 
              v-if="['nao_solicitado', 'rejeitado'].includes(perfil.gr_status)" 
              @click="solicitarAnaliseGr" 
              :disabled="!canClickGrButton" 
              :class="[
                'px-6 py-3 rounded-md text-white font-bold transition-colors shadow-sm flex items-center justify-center gap-2',
                canClickGrButton ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-400 cursor-not-allowed opacity-80'
              ]">
              <svg v-if="grActionLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ grButtonText }}
            </button>

            <button 
              v-if="perfil.gr_status === 'aguardando_biometria'" 
              @click="showBiometriaModal = true" 
              class="px-6 py-3 rounded-md text-indigo-800 bg-indigo-100 border border-indigo-300 font-bold hover:bg-indigo-200 transition-colors shadow-sm flex items-center justify-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
              Realizar Prova de Vida
            </button>
          </div>
        </div>

        <div class="border-t border-gray-200 pt-6">
          <h4 class="text-md font-bold text-gray-800 mb-4">Envio de Documentos (KYC Interno)</h4>
          <p class="text-sm text-gray-500 mb-6">Envie fotos legíveis ou PDFs. Imagens serão comprimidas automaticamente. <strong class="text-red-500">O envio altera o status para "Em Análise".</strong></p>

          <form @submit.prevent="submitDocumentos" class="space-y-6">
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm">
              <label class="block text-sm font-bold text-gray-700">Foto da CNH</label>
              <div class="mt-2 flex items-center justify-between">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'cnh')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <a v-if="perfil.doc_cnh_url" :href="perfil.doc_cnh_url" target="_blank" class="text-xs text-blue-600 underline ml-4 whitespace-nowrap">Ver Atual</a>
              </div>
            </div>

            <div class="bg-blue-50 p-4 border border-blue-200 rounded-lg shadow-sm">
              <label class="block text-sm font-bold text-blue-900">Prova de Vida (Selfie com CNH)</label>
              <div class="flex items-center justify-between mt-2">
                <input type="file" accept="image/*" capture="user" @change="(e) => handleFileUpload(e, 'selfie_cnh')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-blue-100 file:text-blue-800 hover:file:bg-blue-200" />
                <a v-if="perfil.doc_selfie_cnh_url" :href="perfil.doc_selfie_cnh_url" target="_blank" class="text-xs text-blue-800 underline ml-4 whitespace-nowrap">Ver Atual</a>
              </div>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm">
              <label class="block text-sm font-bold text-gray-700">Documento RNTRC</label>
              <div class="mt-2 flex items-center justify-between">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'rntrc')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <a v-if="perfil.doc_rntrc_url" :href="perfil.doc_rntrc_url" target="_blank" class="text-xs text-blue-600 underline ml-4 whitespace-nowrap">Ver Atual</a>
              </div>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm">
              <label class="block text-sm font-bold text-gray-700">Comprovante de Endereço</label>
              <div class="mt-2 flex items-center justify-between">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'endereco')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <a v-if="perfil.doc_comprovante_endereco_url" :href="perfil.doc_comprovante_endereco_url" target="_blank" class="text-xs text-blue-600 underline ml-4 whitespace-nowrap">Ver Atual</a>
              </div>
            </div>

            <div class="flex justify-end pt-4">
              <button type="submit" :disabled="!hasArquivosSelecionados || actionLoading" class="px-6 py-2 rounded-md text-white bg-blue-600 font-bold hover:bg-blue-700 disabled:opacity-50 transition-colors">
                {{ actionLoading ? 'Enviando...' : 'Salvar Documentos' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="showBiometriaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4 transition-opacity">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 bg-indigo-600 flex justify-between items-center">
          <h3 class="text-lg font-bold text-white">Validação Facial</h3>
          <button @click="showBiometriaModal = false" class="text-indigo-200 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        
        <div class="p-6 text-center">
          <p class="text-sm text-gray-600 mb-6">Aponte a câmera do seu telemóvel para o QR Code abaixo para realizar a prova de vida da Trans Sat. O processo é rápido e seguro.</p>
          
          <div class="flex justify-center mb-6">
            <div class="p-2 bg-white border-2 border-gray-200 rounded-lg shadow-sm">
              <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(linkBiometria)}`" alt="QR Code Biometria" class="w-48 h-48 object-contain" />
            </div>
          </div>

          <div class="relative flex py-2 items-center mb-4">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink-0 mx-4 text-gray-400 text-xs uppercase font-semibold">Ou copie o link</span>
            <div class="flex-grow border-t border-gray-300"></div>
          </div>

          <div class="flex mt-2 rounded-md shadow-sm">
            <div class="relative flex-grow focus-within:z-10">
              <input type="text" readonly :value="linkBiometria" class="block w-full rounded-none rounded-l-md border-gray-300 bg-gray-50 text-sm py-3 px-4 text-gray-700 outline-none" />
            </div>
            <button @click="copiarLinkBiometria" :class="['relative -ml-px inline-flex items-center space-x-2 rounded-r-md border border-gray-300 px-4 py-2 text-sm font-medium focus:outline-none transition-colors', copiado ? 'bg-green-500 text-white border-green-500' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
              <svg v-if="!copiado" class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
              <svg v-else class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              <span>{{ copiado ? 'Copiado!' : 'Copiar' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
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
  const classes = { pendente: 'bg-yellow-50 border-yellow-200 text-yellow-800', em_analise: 'bg-blue-50 border-blue-200 text-blue-800', aprovado: 'bg-green-50 border-green-200 text-green-800', rejeitado: 'bg-red-50 border-red-200 text-red-800' };
  return classes[status] || 'bg-gray-50 border-gray-200 text-gray-800';
};

const getStatusMensagem = (status) => {
  const msgs = { pendente: 'Você precisa enviar seus documentos obrigatórios.', em_analise: 'Seus documentos estão na fila de auditoria.', aprovado: 'Auditoria interna concluída.', rejeitado: 'Houve um problema na auditoria. Reenvie arquivos legíveis.' };
  return msgs[status] || '';
};

const getGrStatusAlertClass = (status) => {
  const classes = {
    nao_solicitado: 'bg-gray-50 border-gray-200 text-gray-800',
    pendente: 'bg-blue-50 border-blue-200 text-blue-800',
    aprovado: 'bg-green-50 border-green-200 text-green-800',
    rejeitado: 'bg-red-50 border-red-200 text-red-800',
    aguardando_biometria: 'bg-indigo-50 border-indigo-200 text-indigo-800'
  };
  return classes[status] || 'bg-gray-50 border-gray-200 text-gray-800';
};

const getGrStatusMensagem = (status) => {
  const msgs = {
    nao_solicitado: 'Clique no botão abaixo para submeter o seu perfil à Gerenciadora de Risco.',
    pendente: 'Os seus dados estão a ser processados pela Gerenciadora de Risco. Este processo pode levar alguns minutos.',
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