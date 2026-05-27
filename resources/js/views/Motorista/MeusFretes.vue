<template>
  <div class="space-y-4 sm:space-y-6 relative pb-safe-bottom">
    <div class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden">
      
      <div class="px-4 py-4 sm:px-6 border-b border-surface-200 bg-surface-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-base sm:text-lg font-bold text-surface-900 tracking-tight">Meus Fretes (Gestão de Viagem)</h3>
        <button @click="fetchMinhasCargas" :disabled="loading" class="w-full sm:w-auto text-sm font-bold text-brand-600 hover:text-brand-800 flex items-center justify-center gap-2 disabled:opacity-50 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-md py-2 px-4 bg-brand-50 hover:bg-brand-100 sm:bg-transparent sm:hover:bg-transparent sm:py-1">
          <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          {{ loading ? 'Sincronizando...' : 'Atualizar Lista' }}
        </button>
      </div>

      <div v-if="loading && (!cargas || cargas.length === 0)" class="p-8 text-center text-surface-500 font-medium flex flex-col items-center">
        <svg class="w-8 h-8 animate-spin mb-3 text-brand-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        Buscando seus fretes...
      </div>

      <div v-else-if="!cargas || cargas?.length === 0" class="p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <h3 class="mt-4 text-sm font-bold text-surface-900">Nenhum frete em andamento</h3>
        <p class="mt-1 text-sm text-surface-500">Vá ao Mural para enviar lances e buscar novas cargas.</p>
      </div>

      <template v-else>
        <div class="block lg:hidden divide-y divide-surface-200">
          <div v-for="carga in cargas" :key="'mob-' + carga.id" class="p-4 bg-white hover:bg-surface-50 transition-colors">
            
            <div class="flex justify-between items-start mb-3">
              <span v-if="!carga.motorista_id" class="px-2 py-1 text-[10px] font-black rounded-sm bg-brand-50 text-brand-700 border border-brand-200 uppercase tracking-widest shadow-clinical-sm animate-pulse">
                ⏳ Lance Pendente
              </span>
              <span v-else :class="['px-2 py-1 text-[10px] font-black rounded-sm uppercase tracking-widest shadow-clinical-sm border', getStatusClass(carga.status)]">
                {{ carga.status?.replace(/_/g, ' ') || 'Indefinido' }}
              </span>
            </div>

            <div class="mb-4">
              <div class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-0.5">Embarcador</div>
              <div class="text-sm font-bold text-surface-900 leading-tight">{{ carga.embarcador?.razao_social || 'Empresa Privada' }}</div>
            </div>
            
            <div class="bg-surface-50 rounded-md p-3 border border-surface-200 mb-4">
              <div class="flex items-center justify-between text-sm font-bold text-surface-800 gap-2">
                <span class="truncate flex-1">{{ carga.cidade_origem || 'N/A' }}</span>
                <span class="text-surface-400 flex-shrink-0">➔</span>
                <span class="truncate flex-1 text-right">{{ carga.cidade_destino || 'N/A' }}</span>
              </div>
            </div>
            
            <div class="flex flex-col gap-2">
              <template v-if="!carga.motorista_id">
                 <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="w-full flex justify-center items-center px-4 py-3 border border-red-600 text-red-600 font-bold rounded-md hover:bg-red-50 disabled:opacity-50 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                  Retirar Lance
                </button>
              </template>

              <template v-else>
                <button v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'em_transito', 'em_auditoria', 'entregue'].includes(carga.status)" @click="abrirChat(carga)" class="w-full flex justify-center items-center px-4 py-3 bg-surface-100 border border-surface-300 text-surface-800 font-bold rounded-md shadow-clinical-sm hover:bg-surface-200 transition-colors focus:outline-none focus:ring-2 focus:ring-surface-500">
                  💬 Chat da Operação
                </button>

                <template v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'aguardando_biometria'].includes(carga.status)">
                  <button @click="iniciarViagem(carga.id)" :disabled="actionLoading === carga.id || ['em_analise_gr', 'aguardando_biometria'].includes(carga.status)" :class="['w-full flex justify-center items-center px-4 py-3 font-bold rounded-md transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-brand-500', ['em_analise_gr', 'aguardando_biometria'].includes(carga.status) ? 'bg-surface-200 text-surface-400 cursor-not-allowed' : 'bg-brand-600 text-white hover:bg-brand-700']">
                    {{ actionLoading === carga.id ? 'Processando...' : (['em_analise_gr', 'aguardando_biometria'].includes(carga.status) ? '⏳ Bloqueado (GR)' : '▶ Iniciar Viagem') }}
                  </button>
                  <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="w-full flex justify-center items-center px-4 py-3 border border-red-600 text-red-600 font-bold rounded-md hover:bg-red-50 disabled:opacity-50 transition-colors shadow-clinical-sm mt-1 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Desistir da Viagem
                  </button>
                </template>

                <template v-else-if="carga.status === 'em_transito'">
                  <router-link :to="{ name: 'RastreadorFrete', params: { id: carga.id } }" class="w-full flex justify-center items-center px-4 py-3 bg-surface-900 text-white font-bold rounded-md hover:bg-surface-800 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-surface-900">
                    📍 GPS & Rota
                  </router-link>
                  <button @click="abrirModalFinalizacao(carga)" class="w-full flex justify-center items-center px-4 py-3 bg-brand-600 text-white font-bold rounded-md hover:bg-brand-700 transition-colors shadow-clinical-sm mt-1 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    ✔ Comprovar Entrega
                  </button>
                </template>

                <template v-else-if="carga.status === 'entregue' || carga.status === 'em_auditoria' || carga.status === 'finalizada' || carga.status === 'concluida'">
                  <div class="w-full text-center px-4 py-3 bg-emerald-50 text-emerald-700 font-bold rounded-md border border-emerald-200 text-sm shadow-clinical-sm mt-1">
                    ✔ Em Auditoria
                  </div>
                </template>
                
                <template v-else-if="carga.status === 'em_disputa'">
                  <div class="w-full text-center px-4 py-3 bg-red-50 text-red-700 font-bold rounded-md border border-red-200 text-sm shadow-clinical-sm mt-1">
                    ⚠️ Bloqueado (Disputa)
                  </div>
                </template>
              </template>
            </div>

          </div>
        </div>

        <div class="hidden lg:block w-full overflow-x-auto scrollbar-clinical">
          <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
              <tr>
                <th class="px-6 py-3 text-left text-[11px] font-black text-surface-500 uppercase tracking-wider">Status da Operação</th>
                <th class="px-6 py-3 text-left text-[11px] font-black text-surface-500 uppercase tracking-wider">Rota / Embarcador</th>
                <th class="px-6 py-3 text-right text-[11px] font-black text-surface-500 uppercase tracking-wider">Ações da Viagem</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-surface-200">
              <tr v-for="carga in cargas" :key="'desk-' + carga.id" class="hover:bg-surface-50 transition-colors">
                
                <td class="px-6 py-4 whitespace-nowrap">
                  <span v-if="!carga.motorista_id" class="px-3 py-1 inline-flex text-[10px] leading-5 font-black rounded-sm bg-brand-50 text-brand-700 border border-brand-200 uppercase tracking-widest shadow-clinical-sm animate-pulse">
                    ⏳ LANCE PENDENTE
                  </span>
                  <span v-else :class="['px-3 py-1 inline-flex text-[10px] leading-5 font-black rounded-sm border uppercase tracking-widest shadow-clinical-sm', getStatusClass(carga.status)]">
                    {{ carga.status?.replace(/_/g, ' ') || 'Indefinido' }}
                  </span>
                </td>
                
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-bold text-surface-900">{{ carga.cidade_origem || 'N/A' }} → {{ carga.cidade_destino || 'N/A' }}</div>
                  <div class="text-sm text-surface-500 mt-0.5">{{ carga.embarcador?.razao_social || 'Empresa Privada' }}</div>
                </td>
                
                <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                  <template v-if="!carga.motorista_id">
                     <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-4 py-2 border border-red-600 text-red-600 font-bold rounded-md hover:bg-red-50 disabled:opacity-50 transition-colors shadow-clinical-sm text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                      Retirar Lance
                    </button>
                  </template>

                  <template v-else>
                    <button v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'em_transito', 'em_auditoria', 'entregue'].includes(carga.status)" @click="abrirChat(carga)" class="inline-flex items-center px-4 py-2 bg-surface-100 border border-surface-300 text-surface-800 font-bold rounded-md shadow-clinical-sm hover:bg-surface-200 transition-colors mr-2 text-sm focus:outline-none focus:ring-2 focus:ring-surface-500">
                      💬 Chat
                    </button>

                    <template v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'aguardando_biometria'].includes(carga.status)">
                      <button @click="iniciarViagem(carga.id)" :disabled="actionLoading === carga.id || ['em_analise_gr', 'aguardando_biometria'].includes(carga.status)" :class="['inline-flex items-center px-4 py-2 font-bold rounded-md disabled:opacity-50 transition-colors shadow-clinical-sm text-sm focus:outline-none focus:ring-2 focus:ring-brand-500', ['em_analise_gr', 'aguardando_biometria'].includes(carga.status) ? 'bg-surface-200 text-surface-400 cursor-not-allowed' : 'bg-brand-600 text-white hover:bg-brand-700']">
                        {{ actionLoading === carga.id ? 'Processando...' : (['em_analise_gr', 'aguardando_biometria'].includes(carga.status) ? '⏳ Bloqueado (GR)' : '▶ Iniciar Viagem') }}
                      </button>
                      <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-4 py-2 border border-red-600 text-red-600 font-bold rounded-md hover:bg-red-50 disabled:opacity-50 transition-colors ml-2 shadow-clinical-sm text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                        Desistir
                      </button>
                    </template>

                    <template v-else-if="carga.status === 'em_transito'">
                      <router-link :to="{ name: 'RastreadorFrete', params: { id: carga.id } }" class="inline-flex items-center px-4 py-2 bg-surface-900 text-white font-bold rounded-md hover:bg-surface-800 transition-colors mr-2 shadow-clinical-sm text-sm focus:outline-none focus:ring-2 focus:ring-surface-900">
                        📍 GPS
                      </router-link>
                      <button @click="abrirModalFinalizacao(carga)" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white font-bold rounded-md hover:bg-brand-700 transition-colors shadow-clinical-sm text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        ✔ Comprovar Entrega
                      </button>
                    </template>

                    <template v-else-if="['entregue', 'em_auditoria', 'finalizada', 'concluida'].includes(carga.status)">
                      <span class="text-emerald-600 font-bold text-sm bg-emerald-50 px-3 py-1.5 rounded-md border border-emerald-200">✔ Em Auditoria</span>
                    </template>
                    
                    <template v-else-if="carga.status === 'em_disputa'">
                      <span class="text-red-600 font-bold text-sm bg-red-50 px-3 py-1.5 rounded-md border border-red-200">⚠️ Bloqueado (Disputa)</span>
                    </template>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalFinalizacao" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:p-0">
          <div class="fixed inset-0 bg-surface-950/70 backdrop-blur-sm transition-opacity" @click="fecharModalFinalizacao"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]" aria-hidden="true">&#8203;</span>
          
          <div class="relative inline-block align-bottom sm:align-middle bg-white rounded-t-2xl sm:rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 w-full sm:max-w-lg max-h-[90dvh] flex flex-col pb-safe-bottom sm:pb-0">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 flex-1 overflow-y-auto scrollbar-clinical">
               <h3 class="text-lg font-black text-surface-900 mb-4">Comprovação de Entrega</h3>
               <div class="space-y-5">
                  <div class="bg-surface-50 p-4 rounded-md border border-surface-200">
                    <label class="block text-sm font-bold text-surface-700 mb-2">Foto do Canhoto Assinado <span class="text-red-500">*</span></label>
                    <input type="file" accept="image/*" capture="environment" @change="(e) => handleImageUpload(e, 'canhoto')" class="block w-full text-sm text-surface-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-brand-50 file:text-brand-700 file:font-bold hover:file:bg-brand-100 transition-colors" />
                    <img v-if="previewCanhoto" :src="previewCanhoto" class="mt-3 h-32 object-contain border border-surface-300 rounded shadow-clinical-sm bg-white" />
                  </div>
                  <div class="bg-surface-50 p-4 rounded-md border border-surface-200">
                    <label class="block text-sm font-bold text-surface-700 mb-2">Foto da Carga no Destino <span class="text-red-500">*</span></label>
                    <input type="file" accept="image/*" capture="environment" @change="(e) => handleImageUpload(e, 'carga')" class="block w-full text-sm text-surface-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-brand-50 file:text-brand-700 file:font-bold hover:file:bg-brand-100 transition-colors" />
                    <img v-if="previewCarga" :src="previewCarga" class="mt-3 h-32 object-contain border border-surface-300 rounded shadow-clinical-sm bg-white" />
                  </div>
               </div>
            </div>
            <div class="bg-surface-50 px-4 py-4 sm:px-6 flex flex-col sm:flex-row-reverse border-t border-surface-200 gap-2 shrink-0">
              <button type="button" @click="submitFinalizacao" :disabled="!fotoCanhoto || !fotoCarga || actionLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-transparent shadow-clinical-sm px-4 py-3 sm:py-2 bg-brand-600 text-sm font-bold text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-50 transition-colors">
                <svg v-if="actionLoading" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ actionLoading ? uploadProgress + '% Enviando...' : 'Finalizar Entrega' }}
              </button>
              <button type="button" @click="fecharModalFinalizacao" :disabled="actionLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-surface-300 shadow-clinical-sm px-4 py-3 sm:py-2 bg-white text-sm font-bold text-surface-700 hover:bg-surface-50 focus:outline-none focus:ring-2 focus:ring-surface-300 transition-colors">
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalChat" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:p-0">
          
          <div class="fixed inset-0 bg-surface-950/70 backdrop-blur-sm transition-opacity" @click="fecharChat"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]">&#8203;</span>
          
          <div class="relative inline-block align-bottom bg-white rounded-t-2xl sm:rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle w-full sm:max-w-2xl flex flex-col max-h-[85dvh] sm:h-[600px] pb-safe-bottom sm:pb-0">
            
            <div class="bg-surface-900 px-4 sm:px-6 py-4 flex justify-between items-center shrink-0">
              <div>
                <h3 class="text-white font-black text-sm sm:text-base">Central de Operações <span class="text-brand-400 font-mono">#{{ cargaChatAtivo?.id }}</span></h3>
                <p class="text-surface-400 text-[11px] sm:text-xs mt-0.5">Fale diretamente com a transportadora.</p>
              </div>
              <button @click="fecharChat" class="text-surface-400 hover:text-white font-bold text-2xl leading-none p-1 focus:outline-none transition-colors">&times;</button>
            </div>

            <div class="flex-1 p-4 sm:p-6 overflow-y-auto bg-surface-100 space-y-4 scrollbar-clinical" id="chat-messages">
               <div v-for="msg in mensagensChat" :key="msg.id" :class="['flex', msg.remetente_tipo === 'motorista' ? 'justify-end' : 'justify-start']">
                  <div :class="['max-w-[85%] sm:max-w-[80%] rounded-xl px-4 py-3 shadow-clinical-sm', msg.remetente_tipo === 'motorista' ? 'bg-brand-600 text-white rounded-tr-sm' : 'bg-white border border-surface-200 text-surface-800 rounded-tl-sm']">
                     <div class="text-[9px] font-black uppercase mb-1 opacity-70">{{ msg.remetente_tipo === 'motorista' ? 'Você' : 'Embarcador / Suporte' }}</div>
                     <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ msg.mensagem }}</p>
                  </div>
               </div>
               <div v-if="mensagensChat.length === 0" class="text-center text-surface-400 text-sm mt-10 italic font-medium">A sala de operações está aberta. Envie uma mensagem.</div>
            </div>

            <div class="p-3 sm:p-4 bg-white border-t border-surface-200 shrink-0">
              <form @submit.prevent="enviarMensagemChat" class="flex gap-2 sm:gap-3">
                <input v-model="novaMensagemChat" type="text" placeholder="Escreva a sua mensagem..." class="flex-1 border border-surface-300 rounded-md px-3 sm:px-4 py-3 sm:py-2 text-sm outline-none focus:ring-2 focus:ring-brand-500 placeholder-surface-400 bg-surface-50 focus:bg-white transition-colors" autocomplete="off">
                <button type="submit" :disabled="enviandoMsg || !novaMensagemChat.trim()" class="bg-brand-600 text-white px-4 sm:px-6 font-bold rounded-md text-sm hover:bg-brand-700 disabled:opacity-50 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-brand-500">Enviar</button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </transition>

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
      alocada: 'bg-emerald-50 text-emerald-700 border-emerald-200', 
      em_transito: 'bg-indigo-50 text-indigo-700 border-indigo-200', 
      em_auditoria: 'bg-surface-100 text-surface-800 border-surface-200', 
      entregue: 'bg-emerald-50 text-emerald-700 border-emerald-200', 
      em_disputa: 'bg-rose-50 text-rose-700 border-rose-200',
      em_analise_gr: 'bg-amber-50 text-amber-700 border-amber-200',
      aguardando_biometria: 'bg-surface-900 text-white border-surface-900',
      rejeitado_gr: 'bg-rose-50 text-rose-700 border-rose-200',
      pendente_correcao_gr: 'bg-amber-50 text-amber-700 border-amber-200'
  };
  return classes[status] || 'bg-surface-100 text-surface-800 border-surface-200';
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