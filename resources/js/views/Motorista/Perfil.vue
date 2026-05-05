<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-bold text-gray-800">Minha Conta & Documentação</h3>
        <p class="text-sm text-gray-500">Gerencie seus dados e mantenha sua documentação em dia para receber fretes.</p>
      </div>

      <div v-if="loading" class="p-8 text-center text-gray-500 font-medium">
        Carregando seus dados...
      </div>

      <div v-else class="p-6">
        
        <div :class="['p-4 rounded-md mb-6 border', getStatusAlertClass(perfil.status_verificacao)]">
          <div class="flex items-center">
            <div class="font-bold text-lg capitalize mr-2">
              Status da Conta: {{ perfil.status_verificacao?.replace('_', ' ') || 'Pendente' }}
            </div>
          </div>
          <p class="mt-1 text-sm">
            {{ getStatusMensagem(perfil.status_verificacao) }}
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 bg-gray-50 p-4 rounded-lg border border-gray-100">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase">Nome Completo</label>
            <!-- Alterado para a chave plana do novo contrato -->
            <div class="mt-1 text-sm font-medium text-gray-900">{{ perfil.nome }}</div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase">E-mail</label>
            <!-- Alterado para a chave plana do novo contrato -->
            <div class="mt-1 text-sm font-medium text-gray-900">{{ perfil.email }}</div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase">Telefone</label>
            <!-- Alterado para a chave plana do novo contrato -->
            <div class="mt-1 text-sm font-medium text-gray-900">{{ perfil.telefone }}</div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase">CPF</label>
            <!-- A chave 'cpf' já era plana, mas garantimos que lê direto da raiz -->
            <div class="mt-1 text-sm font-medium text-gray-900">{{ perfil.cpf }}</div>
          </div>
        </div>

        <div class="border-t border-gray-200 pt-6">
          <h4 class="text-md font-bold text-gray-800 mb-4">Envio de Documentos (KYC)</h4>
          <p class="text-sm text-gray-500 mb-6">
            Envie fotos legíveis ou arquivos PDF. Imagens serão comprimidas automaticamente para economizar seus dados. 
            <strong class="text-red-500">Nota: O envio de novos documentos altera seu status para "Em Análise".</strong>
          </p>

          <form @submit.prevent="submitDocumentos" class="space-y-6">
            
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm">
              <label class="block text-sm font-bold text-gray-700">Foto da CNH</label>
              <div class="mt-2 flex items-center justify-between">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'cnh')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <!-- Alterado para usar as propriedades '_url' retornadas pela API -->
                <a v-if="perfil.doc_cnh_url" :href="perfil.doc_cnh_url" target="_blank" class="text-xs text-blue-600 underline ml-4 whitespace-nowrap">Ver Atual</a>
              </div>
              <div v-if="isCompressing.cnh" class="text-xs text-blue-600 mt-2 font-bold">Comprimindo imagem...</div>
            </div>

            <div class="bg-blue-50 p-4 border border-blue-200 rounded-lg shadow-sm">
              <label class="block text-sm font-bold text-blue-900">Prova de Vida (Selfie com CNH)</label>
              <p class="text-xs text-blue-700 mt-1 mb-3">
                Para sua segurança, tire uma foto do seu rosto segurando a sua CNH ao lado. <span class="font-bold">A foto precisa ser nítida.</span>
              </p>
              <div class="flex items-center justify-between">
                <input type="file" accept="image/*" capture="user" @change="(e) => handleFileUpload(e, 'selfie_cnh')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-800 hover:file:bg-blue-200" />
                <!-- Alterado para usar as propriedades '_url' retornadas pela API -->
                <a v-if="perfil.doc_selfie_cnh_url" :href="perfil.doc_selfie_cnh_url" target="_blank" class="text-xs text-blue-800 underline ml-4 whitespace-nowrap">Ver Atual</a>
              </div>
              <div v-if="isCompressing.selfie_cnh" class="text-xs text-blue-800 mt-2 font-bold">Comprimindo imagem...</div>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm">
              <label class="block text-sm font-bold text-gray-700">Documento RNTRC</label>
              <div class="mt-2 flex items-center justify-between">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'rntrc')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <!-- Alterado para usar as propriedades '_url' retornadas pela API -->
                <a v-if="perfil.doc_rntrc_url" :href="perfil.doc_rntrc_url" target="_blank" class="text-xs text-blue-600 underline ml-4 whitespace-nowrap">Ver Atual</a>
              </div>
              <div v-if="isCompressing.rntrc" class="text-xs text-blue-600 mt-2 font-bold">Comprimindo imagem...</div>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm">
              <label class="block text-sm font-bold text-gray-700">Comprovante de Endereço</label>
              <div class="mt-2 flex items-center justify-between">
                <input type="file" accept="image/*,application/pdf" capture="environment" @change="(e) => handleFileUpload(e, 'endereco')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <!-- Alterado para usar as propriedades '_url' retornadas pela API -->
                <a v-if="perfil.doc_comprovante_endereco_url" :href="perfil.doc_comprovante_endereco_url" target="_blank" class="text-xs text-blue-600 underline ml-4 whitespace-nowrap">Ver Atual</a>
              </div>
              <div v-if="isCompressing.endereco" class="text-xs text-blue-600 mt-2 font-bold">Comprimindo imagem...</div>
            </div>

            <div class="flex justify-end pt-4">
              <button 
                type="submit" 
                :disabled="!hasArquivosSelecionados || actionLoading"
                class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-colors"
              >
                {{ actionLoading ? 'Enviando e Atualizando...' : 'Salvar Documentos' }}
              </button>
            </div>

          </form>
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

const arquivos = ref({
  cnh: null,
  selfie_cnh: null,
  rntrc: null,
  endereco: null
});

const isCompressing = ref({
  cnh: false,
  selfie_cnh: false,
  rntrc: false,
  endereco: false
});

const hasArquivosSelecionados = computed(() => {
  return arquivos.value.cnh !== null || 
         arquivos.value.selfie_cnh !== null || 
         arquivos.value.rntrc !== null || 
         arquivos.value.endereco !== null;
});

const getStatusAlertClass = (status) => {
  const classes = {
    pendente: 'bg-yellow-50 border-yellow-200 text-yellow-800',
    em_analise: 'bg-blue-50 border-blue-200 text-blue-800',
    aprovado: 'bg-green-50 border-green-200 text-green-800',
    rejeitado: 'bg-red-50 border-red-200 text-red-800'
  };
  return classes[status] || 'bg-gray-50 border-gray-200 text-gray-800';
};

const getStatusMensagem = (status) => {
  const mensagens = {
    pendente: 'Você precisa enviar seus documentos obrigatórios para que possamos liberar sua conta.',
    em_analise: 'Seus documentos foram recebidos e estão na fila de auditoria. Aguarde a aprovação.',
    aprovado: 'Conta verificada! Você está totalmente liberado para aceitar fretes na plataforma.',
    rejeitado: 'Houve um problema na auditoria de um ou mais documentos. Por favor, reenvie arquivos legíveis.'
  };
  return mensagens[status] || '';
};

// --- Atualizado para consumir a URL baseada na versão do backend (V1) ---
const fetchPerfil = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/motorista/perfil');
    perfil.value = response.data;
  } catch (error) {
    console.error('Erro ao carregar perfil:', error);
    alert('Erro ao carregar os dados da conta. Verifique sua conexão.');
  } finally {
    loading.value = false;
  }
};

const handleFileUpload = async (event, tipo) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.type === 'application/pdf') {
    arquivos.value[tipo] = file;
    return;
  }

  const options = {
    maxSizeMB: 1, 
    maxWidthOrHeight: 1600,
    useWebWorker: true,
  };

  try {
    isCompressing.value[tipo] = true;
    const compressedBlob = await imageCompression(file, options);
    arquivos.value[tipo] = new File([compressedBlob], file.name, { type: compressedBlob.type });
  } catch (error) {
    console.error('Erro na compressão:', error);
    alert('Não foi possível otimizar a imagem. Tente outra ou use formato PDF.');
    arquivos.value[tipo] = file; 
  } finally {
    isCompressing.value[tipo] = false;
  }
};

const submitDocumentos = async () => {
  actionLoading.value = true;

  const formData = new FormData();
  if (arquivos.value.cnh) formData.append('doc_cnh', arquivos.value.cnh);
  if (arquivos.value.selfie_cnh) formData.append('doc_selfie_cnh', arquivos.value.selfie_cnh);
  if (arquivos.value.rntrc) formData.append('doc_rntrc', arquivos.value.rntrc);
  if (arquivos.value.endereco) formData.append('doc_comprovante_endereco', arquivos.value.endereco);

  try {
    // --- Atualizado para a URL V1 de envio de documentos ---
    const response = await axios.post('/api/motorista/perfil/documentos', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    alert(response.data.message);
    
    // O backend retorna apenas a mensagem e o status, refazemos o fetch para garantir que todos
    // os dados da view (URLs de documentos e etc) estejam atualizados e corretos.
    await fetchPerfil();
    await authStore.fetchUser(); 
    
    arquivos.value = { cnh: null, selfie_cnh: null, rntrc: null, endereco: null };
    
  } catch (error) {
    console.error('Erro ao enviar documentos:', error);
    alert(error.response?.data?.message || 'Erro de conexão ao enviar os arquivos.');
  } finally {
    actionLoading.value = false;
  }
};

onMounted(() => {
  fetchPerfil();
});
</script>