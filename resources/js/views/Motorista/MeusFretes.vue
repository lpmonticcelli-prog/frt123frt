<template>
  <div class="space-y-6 relative">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800">Meus Fretes (Gestão de Viagem)</h3>
        <button @click="fetchMinhasCargas" class="text-sm font-bold text-blue-600 hover:text-blue-800">
          Atualizar Lista
        </button>
      </div>

      <div v-if="loading" class="p-8 text-center text-gray-500 font-medium">Buscando seus fretes...</div>

      <div v-else-if="!cargas || cargas?.length === 0" class="p-12 text-center">
        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum frete em andamento</h3>
        <p class="mt-1 text-sm text-gray-500">Vá ao Mural para enviar lances.</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Rota / Embarcador</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Ações da Viagem</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="carga in cargas" :key="carga.id" class="hover:bg-gray-50">
            
            <td class="px-6 py-4 whitespace-nowrap">
              <span v-if="!carga.motorista_id" class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-blue-100 text-blue-800 uppercase shadow-sm animate-pulse">
                ⏳ LANCE PENDENTE
              </span>
              <span v-else :class="['px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full uppercase', getStatusClass(carga.status)]">
                {{ carga.status?.replace(/_/g, ' ') || 'Indefinido' }}
              </span>
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-gray-900">{{ carga.cidade_origem || 'N/A' }} → {{ carga.cidade_destino || 'N/A' }}</div>
              <div class="text-sm text-gray-500">{{ carga.embarcador?.razao_social || 'Empresa Privada' }}</div>
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
              
              <template v-if="!carga.motorista_id">
                 <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-3 py-1.5 border border-red-600 text-red-600 font-bold rounded hover:bg-red-50 disabled:opacity-50 transition-colors">
                  Retirar Lance
                </button>
              </template>

              <template v-else>
                
                <button v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'em_transito', 'em_auditoria', 'entregue'].includes(carga.status)" @click="abrirChat(carga)" class="inline-flex items-center px-3 py-1.5 bg-blue-100 border border-blue-300 text-blue-800 font-bold rounded shadow-sm hover:bg-blue-200 transition-colors mr-2">
                  💬 Chat
                </button>

                <template v-if="carga.status === 'alocada' || carga.status === 'aguardando_coleta' || carga.status === 'processando_aceite' || carga.status === 'em_analise_gr'">
                  <button @click="iniciarViagem(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white font-bold rounded hover:bg-green-700 disabled:opacity-50 transition-colors">
                    {{ actionLoading === carga.id ? 'Processando...' : '▶ Iniciar Viagem' }}
                  </button>
                  <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-3 py-1.5 border border-red-600 text-red-600 font-bold rounded hover:bg-red-50 disabled:opacity-50 transition-colors ml-2">
                    Desistir
                  </button>
                </template>

                <template v-else-if="carga.status === 'em_transito'">
                  <router-link :to="{ name: 'RastreadorFrete', params: { id: carga.id } }" class="inline-flex items-center px-3 py-1.5 bg-gray-900 text-white font-bold rounded hover:bg-gray-800 transition-colors mr-2">
                    📍 GPS
                  </router-link>
                  <button @click="abrirModalFinalizacao(carga)" class="inline-flex items-center px-4 py-1.5 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 transition-colors">
                    ✔ Comprovar Entrega
                  </button>
                </template>

                <template v-else-if="carga.status === 'entregue' || carga.status === 'em_auditoria' || carga.status === 'finalizada' || carga.status === 'concluida'">
                  <span class="text-green-600 font-bold text-sm">✔ Em Auditoria</span>
                </template>
                
                <template v-else-if="carga.status === 'em_disputa'">
                  <span class="text-red-600 font-bold text-sm">⚠️ Bloqueado (Disputa)</span>
                </template>
              </template>

            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModalFinalizacao" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="fecharModalFinalizacao"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
             <h3 class="text-lg font-bold text-gray-900">Comprovação de Entrega</h3>
             <div class="mt-4 space-y-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700">Foto do Canhoto Assinado <span class="text-red-500">*</span></label>
                  <input type="file" accept="image/*" capture="environment" @change="(e) => handleImageUpload(e, 'canhoto')" class="mt-1 block w-full text-sm text-gray-500 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700" />
                  <img v-if="previewCanhoto" :src="previewCanhoto" class="mt-2 h-24 object-contain border rounded" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700">Foto da Carga no Destino <span class="text-red-500">*</span></label>
                  <input type="file" accept="image/*" capture="environment" @change="(e) => handleImageUpload(e, 'carga')" class="mt-1 block w-full text-sm text-gray-500 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700" />
                  <img v-if="previewCarga" :src="previewCarga" class="mt-2 h-24 object-contain border rounded" />
                </div>
             </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="button" @click="submitFinalizacao" :disabled="!fotoCanhoto || !fotoCarga || actionLoading" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
              {{ actionLoading ? uploadProgress + '% Enviando...' : 'Finalizar Entrega' }}
            </button>
            <button type="button" @click="fecharModalFinalizacao" :disabled="actionLoading" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModalChat" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div class="fixed inset-0 bg-slate-900 bg-opacity-80 transition-opacity backdrop-blur-sm" @click="fecharChat"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full z-10">
          
          <div class="flex flex-col h-[500px] sm:h-[600px]">
            
            <div class="bg-slate-900 px-6 py-4 flex justify-between items-center shrink-0">
              <div>
                <h3 class="text-white font-black">Central de Operações #{{ cargaChatAtivo?.id }}</h3>
                <p class="text-slate-400 text-xs mt-1">Fale diretamente com o parceiro logístico.</p>
              </div>
              <button @click="fecharChat" class="text-slate-400 hover:text-white font-bold text-xl leading-none">&times;</button>
            </div>

            <div class="flex-1 p-6 overflow-y-auto bg-slate-50 space-y-4" id="chat-messages">
               <div v-for="msg in mensagensChat" :key="msg.id" :class="['flex', msg.remetente_tipo === 'motorista' ? 'justify-end' : 'justify-start']">
                  <div :class="['max-w-[80%] rounded-lg px-4 py-3 shadow-sm', msg.remetente_tipo === 'motorista' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none']">
                     <div class="text-[9px] font-black uppercase mb-1 opacity-70">{{ msg.remetente_tipo === 'motorista' ? 'Você' : 'Embarcador' }}</div>
                     <p class="text-sm whitespace-pre-wrap">{{ msg.mensagem }}</p>
                  </div>
               </div>
               <div v-if="mensagensChat.length === 0" class="text-center text-slate-400 text-sm mt-10 italic">A sala de operações está aberta. Envie uma mensagem.</div>
            </div>

            <div class="p-4 bg-white border-t border-slate-200 shrink-0">
              <form @submit.prevent="enviarMensagemChat" class="flex gap-3">
                <input v-model="novaMensagemChat" type="text" placeholder="Escreva a sua mensagem..." class="flex-1 border border-slate-300 rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                <button type="submit" :disabled="enviandoMsg || !novaMensagemChat.trim()" class="bg-blue-600 text-white px-6 font-bold rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">Enviar</button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import imageCompression from 'browser-image-compression';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const cargas = ref([]);
const loading = ref(true);
const actionLoading = ref(false);
const uploadProgress = ref(0);

const showModalFinalizacao = ref(false); 
const showModalChat = ref(false);
const cargaSelecionada = ref(null);
const cargaChatAtivo = ref(null);

const fotoCanhoto = ref(null);
const fotoCarga = ref(null);
const previewCanhoto = ref(null);
const previewCarga = ref(null);

const mensagensChat = ref([]);
const novaMensagemChat = ref('');
const enviandoMsg = ref(false);

const getStatusClass = (status) => {
  const classes = { 
      alocada: 'bg-green-100 text-green-800 border-green-200', 
      em_transito: 'bg-purple-100 text-purple-800 border-purple-200', 
      em_auditoria: 'bg-blue-100 text-blue-800 border-blue-200', 
      entregue: 'bg-green-100 text-green-800 border-green-200', 
      em_disputa: 'bg-red-100 text-red-800 border-red-200',
      em_analise_gr: 'bg-yellow-100 text-yellow-800 border-yellow-200' 
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const fetchMinhasCargas = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/motorista/cargas/minhas');
    cargas.value = res.data.data ? res.data.data : res.data;
  } catch (error) { 
    console.error('Erro:', error); 
  } finally { 
    loading.value = false; 
  }
};

const cancelarAceite = async (id) => {
  if (!confirm('Tem certeza que deseja retirar seu lance/desistir?')) return;
  actionLoading.value = id;
  try {
    await axios.delete(`/api/v1/motorista/cargas/${id}/aceitar`);
    fetchMinhasCargas();
  } catch (error) { alert(error.response?.data?.message || 'Erro ao cancelar o frete.'); } finally { actionLoading.value = false; }
};

const iniciarViagem = async (id) => {
  if (!confirm('Confirma que iniciou o deslocamento? O GPS será ativado.')) return;
  actionLoading.value = id;
  try {
    await axios.post(`/api/v1/motorista/cargas/${id}/iniciar-viagem`);
    router.push({ name: 'RastreadorFrete', params: { id: id } });
  } catch (error) { 
      alert(error.response?.data?.message || error.response?.data?.error || 'Aguarde o status ser aprovado para iniciar viagem.'); 
  } finally { 
      actionLoading.value = false; 
  }
};

const abrirModalFinalizacao = (carga) => { cargaSelecionada.value = carga; showModalFinalizacao.value = true; };
const fecharModalFinalizacao = () => { showModalFinalizacao.value = false; cargaSelecionada.value = null; fotoCanhoto.value = null; fotoCarga.value = null; previewCanhoto.value = null; previewCarga.value = null; uploadProgress.value = 0;};

const handleImageUpload = async (event, tipo) => {
  const file = event.target.files[0];
  if (!file) return;
  try {
    const compressedFile = await imageCompression(file, { maxSizeMB: 1, maxWidthOrHeight: 1600 });
    const finalFile = new File([compressedFile], compressedFile.name || "foto.jpg", { type: compressedFile.type });
    const reader = new FileReader();
    reader.onload = (e) => {
      if (tipo === 'canhoto') { fotoCanhoto.value = finalFile; previewCanhoto.value = e.target.result; } 
      else { fotoCarga.value = finalFile; previewCarga.value = e.target.result; }
    };
    reader.readAsDataURL(finalFile);
  } catch (error) { alert("Erro ao processar imagem."); }
};

const submitFinalizacao = async () => {
  if (!fotoCanhoto.value || !fotoCarga.value) return alert('As duas fotos são obrigatórias.');
  actionLoading.value = true; uploadProgress.value = 10;
  
  try {
    const formData = new FormData();
    formData.append('foto_canhoto', fotoCanhoto.value);
    formData.append('foto_carga', fotoCarga.value);

    await axios.post(`/api/v1/motorista/cargas/${cargaSelecionada.value.id}/finalizar`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        }
    });

    alert('Entrega confirmada! Aguarde a liberação do seu pagamento.');
    fecharModalFinalizacao(); fetchMinhasCargas();
  } catch (error) {
    alert(error.response?.data?.message || 'Falha de conexão com os servidores.');
  } finally {
    actionLoading.value = false; uploadProgress.value = 0;
  }
};

const abrirChat = async (carga) => {
  cargaChatAtivo.value = carga; 
  showModalChat.value = true;
  try {
    const res = await axios.get(`/api/v1/motorista/cargas/${carga.id}/chat`);
    mensagensChat.value = res.data;
    await nextTick();
    const chatContainer = document.getElementById('chat-messages');
    if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;

    // 🔥 CONECTA NO WEBSOCKET EXCLUSIVO DESTA CARGA
    if (window.Echo) {
      window.Echo.channel(`chat.${carga.id}`)
        .listen('.NovaMensagem', (e) => {
          mensagensChat.value.push(e.mensagem);
          nextTick(() => {
            const container = document.getElementById('chat-messages');
            if (container) container.scrollTop = container.scrollHeight;
          });
        });
    }
  } catch (e) { console.error("Erro ao carregar chat", e); }
};

const fecharChat = () => {
  // 🔥 DESCONECTA DO WEBSOCKET PARA NÃO DUPLICAR MENSAGENS
  if (window.Echo && cargaChatAtivo.value) {
    window.Echo.leaveChannel(`chat.${cargaChatAtivo.value.id}`);
  }
  showModalChat.value = false; 
  cargaChatAtivo.value = null; 
  mensagensChat.value = []; 
};

const enviarMensagemChat = async () => {
  if (!novaMensagemChat.value.trim() || !cargaChatAtivo.value) return;
  enviandoMsg.value = true;
  try {
    const res = await axios.post(`/api/v1/motorista/cargas/${cargaChatAtivo.value.id}/chat`, { mensagem: novaMensagemChat.value });
    mensagensChat.value.push(res.data); novaMensagemChat.value = '';
    await nextTick();
    const chatContainer = document.getElementById('chat-messages');
    if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
  } catch (e) {
    if (e.response?.status === 403 && e.response?.data?.error) alert(e.response.data.error);
    else alert('Erro ao enviar mensagem.');
  } finally { enviandoMsg.value = false; }
};

onMounted(() => {
  fetchMinhasCargas();

  if (window.Echo && authStore.user?.id) {
    window.Echo.channel(`motorista.${authStore.user.id}`)
      .listen('.CargaAtualizada', (e) => {
        if (!cargas.value) return;

        const index = cargas.value.findIndex(c => c.id === e.carga.id);
        if (index !== -1) {
          cargas.value[index] = e.carga;
        } else {
          cargas.value.unshift(e.carga);
        }
      });
  }
});

onBeforeUnmount(() => {
  if (window.Echo && authStore.user?.id) {
    window.Echo.leaveChannel(`motorista.${authStore.user.id}`);
  }
});
</script>