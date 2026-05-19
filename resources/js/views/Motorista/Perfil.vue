<template>
  <div class="space-y-6">
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

        <div class="border-t border-gray-200 pt-6">
          <h4 class="text-md font-bold text-gray-800 mb-4">Envio de Documentos (KYC)</h4>
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

const arquivos = ref({ cnh: null, selfie_cnh: null, rntrc: null, endereco: null });

const hasArquivosSelecionados = computed(() => arquivos.value.cnh || arquivos.value.selfie_cnh || arquivos.value.rntrc || arquivos.value.endereco);

const getStatusAlertClass = (status) => {
  const classes = { pendente: 'bg-yellow-50 border-yellow-200 text-yellow-800', em_analise: 'bg-blue-50 border-blue-200 text-blue-800', aprovado: 'bg-green-50 border-green-200 text-green-800', rejeitado: 'bg-red-50 border-red-200 text-red-800' };
  return classes[status] || 'bg-gray-50 border-gray-200 text-gray-800';
};

const getStatusMensagem = (status) => {
  const msgs = { pendente: 'Você precisa enviar seus documentos obrigatórios.', em_analise: 'Seus documentos estão na fila de auditoria.', aprovado: 'Conta verificada! Você está liberado para aceitar fretes.', rejeitado: 'Houve um problema na auditoria. Reenvie arquivos legíveis.' };
  return msgs[status] || '';
};

const fetchPerfil = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/motorista/perfil');
    perfil.value = response.data;
  } catch (error) { alert('Erro ao carregar perfil.'); } finally { loading.value = false; }
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