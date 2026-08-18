<template>
  <div class="animate-fade-in space-y-4 sm:space-y-6 relative pb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      
      <!-- HEADER DO PAINEL -->
      <div class="px-4 py-5 sm:px-6 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Gestão de Viagens (Meus Fretes)</h3>
        <button @click="fetchMinhasCargas" :disabled="loading" class="w-full sm:w-auto text-sm font-bold text-[#035D29] hover:text-[#023818] flex items-center justify-center gap-2 disabled:opacity-50 transition-colors focus:outline-none focus:ring-2 focus:ring-[#035D29]/50 rounded-xl py-3 px-5 bg-white border border-slate-200 shadow-sm sm:bg-transparent sm:border-transparent sm:shadow-none sm:py-2 hover:bg-slate-100">
          <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          <svg v-else class="w-4 h-4 animate-spin text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          {{ loading ? 'Sincronizando...' : 'Atualizar Lista' }}
        </button>
      </div>

      <!-- ESTADO: CARREGANDO -->
      <div v-if="loading && (!cargas || cargas.length === 0)" class="p-12 text-center text-slate-500 font-medium flex flex-col items-center">
        <svg class="w-10 h-10 animate-spin mb-4 text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="text-sm">Buscando seus fretes operacionais...</span>
      </div>

      <!-- ESTADO: VAZIO -->
      <div v-else-if="!cargas || cargas?.length === 0" class="p-16 text-center">
        <div class="mx-auto w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5 border border-slate-100 shadow-inner">
          <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
        </div>
        <h3 class="mt-4 text-lg font-black text-slate-900 tracking-tight">Nenhum frete em andamento</h3>
        <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">Vá ao Mural de Cargas para enviar lances e buscar novas viagens.</p>
        <div class="mt-8">
          <router-link :to="{ name: 'MotoristaMural' }" class="inline-flex items-center rounded-xl bg-[#035D29] px-8 py-3 text-sm font-bold text-white shadow-md hover:bg-[#023818] transition-all">
            Ir para o Mural
          </router-link>
        </div>
      </div>

      <!-- ESTADO: COM CARGAS -->
      <template v-else>
        <!-- MOBILE VIEW -->
        <div class="block lg:hidden divide-y divide-slate-100" :class="{ 'opacity-50 pointer-events-none': loading }">
          <div v-for="carga in cargas" :key="'mob-' + carga.id" class="p-5 bg-white hover:bg-slate-50 transition-colors">
            
            <div class="flex justify-between items-start mb-4">
              <span v-if="!carga.motorista_id" class="px-3 py-1.5 text-[10px] font-black rounded-lg bg-orange-50 text-[#ff5500] border border-orange-200 uppercase tracking-widest shadow-sm animate-pulse">
                ⏳ Lance Pendente
              </span>
              <span v-else :class="['px-3 py-1.5 text-[10px] font-black rounded-lg uppercase tracking-widest shadow-sm border', getStatusClass(carga.status)]">
                {{ carga.status?.replace(/_/g, ' ') || 'Indefinido' }}
              </span>
            </div>

            <div class="mb-4">
              <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Embarcador</div>
              <div class="text-base font-black text-slate-900 leading-tight">{{ carga.embarcador?.razao_social || 'Empresa Privada' }}</div>
            </div>
            
            <!-- Rota (Verticalzada estilo APP) -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 mb-5 flex flex-col items-center text-center shadow-inner">
              <div class="w-full">
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Origem</span>
                <span class="block text-sm font-bold text-slate-900 truncate">{{ carga.cidade_origem || 'N/A' }}</span>
              </div>
              <svg class="w-5 h-5 text-[#035D29] my-2 transform rotate-90 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
              <div class="w-full">
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Destino</span>
                <span class="block text-sm font-bold text-slate-900 truncate">{{ carga.cidade_destino || 'N/A' }}</span>
              </div>
            </div>
            
            <!-- BOTÕES DE AÇÃO (Grandes e fáceis de clicar no celular) -->
            <div class="flex flex-col gap-3">
              <template v-if="!carga.motorista_id">
                 <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="w-full flex justify-center items-center px-4 py-3.5 border-2 border-red-100 text-red-600 font-bold rounded-xl hover:bg-red-50 disabled:opacity-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                  <svg v-if="actionLoading === carga.id" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  Retirar Lance
                </button>
              </template>

              <template v-else>
                <button v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'em_transito', 'em_auditoria', 'entregue'].includes(carga.status)" @click="abrirChat(carga)" class="w-full flex justify-center items-center px-4 py-3.5 bg-slate-100 border border-slate-300 text-slate-800 font-bold rounded-xl shadow-sm hover:bg-slate-200 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-500">
                  💬 Chat da Operação
                </button>

                <template v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'aguardando_biometria'].includes(carga.status)">
                  <button @click="iniciarViagem(carga.id)" :disabled="actionLoading === carga.id || ['em_analise_gr', 'aguardando_biometria'].includes(carga.status)" :class="['w-full flex justify-center items-center px-4 py-3.5 font-bold rounded-xl transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-[#035D29]', ['em_analise_gr', 'aguardando_biometria'].includes(carga.status) ? 'bg-slate-200 text-slate-500 cursor-not-allowed border border-slate-300' : 'bg-[#035D29] text-white hover:bg-[#023818]']">
                    <svg v-if="actionLoading === carga.id" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    {{ actionLoading === carga.id ? 'Processando...' : (['em_analise_gr', 'aguardando_biometria'].includes(carga.status) ? '⏳ Bloqueado (GR)' : '▶ Iniciar Viagem') }}
                  </button>
                  <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="w-full flex justify-center items-center px-4 py-3.5 border-2 border-red-100 text-red-600 font-bold rounded-xl hover:bg-red-50 disabled:opacity-50 transition-colors shadow-sm mt-1 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Desistir da Viagem
                  </button>
                </template>

                <template v-else-if="carga.status === 'em_transito'">
                  <button @click="abrirModalFinalizacao(carga)" class="w-full flex justify-center items-center px-4 py-3.5 bg-[#035D29] text-white font-bold rounded-xl hover:bg-[#023818] transition-colors shadow-md focus:outline-none focus:ring-2 focus:ring-[#035D29]">
                    ✔ Comprovar Entrega
                  </button>
                </template>

                <template v-else-if="carga.status === 'entregue' || carga.status === 'em_auditoria' || carga.status === 'finalizada' || carga.status === 'concluida'">
                  <div class="w-full text-center px-4 py-3.5 bg-emerald-50 text-emerald-800 font-black tracking-widest uppercase rounded-xl border border-emerald-200 text-xs shadow-sm mt-1">
                    ✔ Em Auditoria
                  </div>
                </template>
                
                <template v-else-if="carga.status === 'em_disputa'">
                  <div class="w-full text-center px-4 py-3.5 bg-red-50 text-red-800 font-black tracking-widest uppercase rounded-xl border border-red-200 text-xs shadow-sm mt-1">
                    ⚠️ Bloqueado (Disputa)
                  </div>
                </template>
              </template>
            </div>

          </div>
        </div>

        <!-- DESKTOP VIEW -->
        <div class="hidden lg:block w-full overflow-x-auto scrollbar-clinical" :class="{ 'opacity-50 pointer-events-none': loading }">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Status da Operação</th>
                <th class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Rota / Embarcador</th>
                <th class="px-6 py-4 text-right text-[11px] font-black text-slate-500 uppercase tracking-wider">Ações da Viagem</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
              <tr v-for="carga in cargas" :key="'desk-' + carga.id" class="hover:bg-slate-50/80 transition-colors">
                
                <td class="px-6 py-5 whitespace-nowrap">
                  <span v-if="!carga.motorista_id" class="px-3 py-1.5 inline-flex text-[10px] leading-5 font-black rounded-lg bg-orange-50 text-[#ff5500] border border-orange-200 uppercase tracking-widest shadow-sm animate-pulse">
                    ⏳ LANCE PENDENTE
                  </span>
                  <span v-else :class="['px-3 py-1.5 inline-flex text-[10px] leading-5 font-black rounded-lg border uppercase tracking-widest shadow-sm', getStatusClass(carga.status)]">
                    {{ carga.status?.replace(/_/g, ' ') || 'Indefinido' }}
                  </span>
                </td>
                
                <td class="px-6 py-5 whitespace-nowrap">
                  <div class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                    {{ carga.cidade_origem || 'N/A' }} 
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    {{ carga.cidade_destino || 'N/A' }}
                  </div>
                  <div class="text-[11px] font-black text-slate-400 mt-1 uppercase tracking-wider">{{ carga.embarcador?.razao_social || 'Empresa Privada' }}</div>
                </td>
                
                <td class="px-6 py-5 whitespace-nowrap text-right space-x-3">
                  <template v-if="!carga.motorista_id">
                     <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-5 py-2.5 border-2 border-red-100 text-red-600 font-bold rounded-xl hover:bg-red-50 disabled:opacity-50 transition-colors shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                      Retirar Lance
                    </button>
                  </template>

                  <template v-else>
                    <button v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'em_transito', 'em_auditoria', 'entregue'].includes(carga.status)" @click="abrirChat(carga)" class="inline-flex items-center px-4 py-2.5 bg-slate-100 border border-slate-300 text-slate-800 font-bold rounded-xl shadow-sm hover:bg-slate-200 transition-colors text-sm focus:outline-none focus:ring-2 focus:ring-slate-500">
                      💬 Chat
                    </button>

                    <template v-if="['alocada', 'aguardando_coleta', 'processando_aceite', 'em_analise_gr', 'aguardando_biometria'].includes(carga.status)">
                      <button @click="iniciarViagem(carga.id)" :disabled="actionLoading === carga.id || ['em_analise_gr', 'aguardando_biometria'].includes(carga.status)" :class="['inline-flex items-center px-6 py-2.5 font-bold rounded-xl disabled:opacity-50 transition-colors shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-[#035D29]', ['em_analise_gr', 'aguardando_biometria'].includes(carga.status) ? 'bg-slate-200 text-slate-500 border border-slate-300 cursor-not-allowed' : 'bg-[#035D29] text-white hover:bg-[#023818]']">
                        {{ actionLoading === carga.id ? 'Processando...' : (['em_analise_gr', 'aguardando_biometria'].includes(carga.status) ? '⏳ Bloqueado (GR)' : '▶ Iniciar Viagem') }}
                      </button>
                      <button @click="cancelarAceite(carga.id)" :disabled="actionLoading === carga.id" class="inline-flex items-center px-4 py-2.5 border-2 border-red-100 text-red-600 font-bold rounded-xl hover:bg-red-50 disabled:opacity-50 transition-colors shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                        Desistir
                      </button>
                    </template>

                    <template v-else-if="carga.status === 'em_transito'">
                      <button @click="abrirModalFinalizacao(carga)" class="inline-flex items-center px-6 py-2.5 bg-[#035D29] text-white font-bold rounded-xl hover:bg-[#023818] transition-colors shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-[#035D29]">
                        ✔ Comprovar Entrega
                      </button>
                    </template>

                    <template v-else-if="['entregue', 'em_auditoria', 'finalizada', 'concluida'].includes(carga.status)">
                      <span class="text-emerald-700 font-black uppercase tracking-widest text-[10px] bg-emerald-50 px-4 py-2 rounded-lg border border-emerald-200">✔ Em Auditoria</span>
                    </template>
                    
                    <template v-else-if="carga.status === 'em_disputa'">
                      <span class="text-red-700 font-black uppercase tracking-widest text-[10px] bg-red-50 px-4 py-2 rounded-lg border border-red-200">⚠️ Bloqueado (Disputa)</span>
                    </template>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- MODAL DE FINALIZAÇÃO (UPLOAD DE FOTOS - BLINDADO) -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalFinalizacao" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:items-center sm:p-0">
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="fecharModalFinalizacao"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]" aria-hidden="true">&#8203;</span>
          
          <div class="relative flex align-bottom sm:align-middle bg-white rounded-t-2xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 w-full sm:w-11/12 sm:max-w-xl max-h-[90dvh] flex-col pb-safe-bottom sm:pb-0 border border-slate-200">
            <div class="bg-white px-5 pt-6 pb-6 sm:px-8 sm:py-8 flex-1 overflow-y-auto scrollbar-clinical">
               <div class="flex justify-between items-center border-b border-slate-100 pb-4 mb-6">
                 <h3 class="text-xl font-black text-slate-900 tracking-tight">Comprovar Entrega</h3>
                 <button @click="fecharModalFinalizacao" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                 </button>
               </div>
               
               <p class="text-sm text-slate-500 mb-6 leading-relaxed font-medium text-left">Para liberar o pagamento, envie a foto nítida do canhoto assinado e da carga descarregada no destino.</p>

               <div class="space-y-6">
                  <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-inner">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-3">1. Foto do Canhoto Assinado <span class="text-red-500 text-base">*</span></label>
                    <input type="file" accept="image/*" capture="environment" @change="(e) => handleImageUpload(e, 'canhoto')" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:bg-emerald-50 file:text-[#035D29] file:font-bold hover:file:bg-emerald-100 transition-colors cursor-pointer outline-none" />
                    <img v-if="previewCanhoto" :src="previewCanhoto" class="mt-4 h-32 object-contain border border-slate-300 rounded-lg shadow-sm bg-white" />
                  </div>
                  
                  <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-inner">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-3">2. Foto da Carga no Destino <span class="text-red-500 text-base">*</span></label>
                    <input type="file" accept="image/*" capture="environment" @change="(e) => handleImageUpload(e, 'carga')" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:bg-emerald-50 file:text-[#035D29] file:font-bold hover:file:bg-emerald-100 transition-colors cursor-pointer outline-none" />
                    <img v-if="previewCarga" :src="previewCarga" class="mt-4 h-32 object-contain border border-slate-300 rounded-lg shadow-sm bg-white" />
                  </div>
               </div>
            </div>
            
            <div class="bg-slate-50 px-5 py-5 sm:px-8 flex flex-col sm:flex-row-reverse border-t border-slate-200 gap-3 shrink-0">
              <button type="button" @click="submitFinalizacao" :disabled="!fotoCanhoto || !fotoCarga || actionLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl shadow-md px-6 py-3.5 sm:py-2.5 bg-[#035D29] text-sm font-bold text-white hover:bg-[#023818] focus:outline-none focus:ring-2 focus:ring-[#035D29] disabled:opacity-50 transition-colors">
                <svg v-if="actionLoading" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ actionLoading ? uploadProgress + '% Enviando...' : 'Finalizar Entrega' }}
              </button>
              <button type="button" @click="fecharModalFinalizacao" :disabled="actionLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border border-slate-300 shadow-sm px-6 py-3.5 sm:py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-100 focus:outline-none transition-colors">
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL DE CHAT (BLINDADO) -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalChat" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:items-center sm:p-0">
          
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="fecharChat"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]">&#8203;</span>
          
          <div class="relative flex align-bottom bg-white rounded-t-2xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle w-full sm:w-11/12 sm:max-w-2xl flex-col max-h-[90dvh] sm:h-[600px] pb-safe-bottom sm:pb-0 border border-slate-200">
            
            <div class="bg-slate-900 px-5 py-4 sm:px-6 flex justify-between items-center shrink-0">
              <div>
                <h3 class="text-white font-black tracking-tight text-lg">Central de Operações <span class="text-[#ff5500] font-mono ml-1">#{{ cargaChatAtivo?.id }}</span></h3>
                <p class="text-slate-400 text-xs mt-1 font-medium">Fale diretamente com o Embarcador/Suporte.</p>
              </div>
              <button @click="fecharChat" class="text-slate-400 hover:text-white font-bold text-3xl leading-none focus:outline-none transition-colors">&times;</button>
            </div>

            <div class="flex-1 p-5 sm:p-6 overflow-y-auto bg-slate-50 space-y-4 scrollbar-clinical shadow-inner" id="chat-messages">
               <div v-for="msg in mensagensChat" :key="msg.id" :class="['flex', msg.remetente_tipo === 'motorista' ? 'justify-end' : 'justify-start']">
                  <div :class="['max-w-[85%] sm:max-w-[80%] rounded-2xl px-5 py-3 shadow-sm', msg.remetente_tipo === 'motorista' ? 'bg-[#035D29] text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none']">
                     <div class="text-[9px] font-black uppercase tracking-widest mb-1 opacity-70">{{ msg.remetente_tipo === 'motorista' ? 'Você' : 'Embarcador' }}</div>
                     <p class="text-sm whitespace-pre-wrap leading-relaxed font-medium">{{ msg.mensagem }}</p>
                  </div>
               </div>
               <div v-if="mensagensChat.length === 0" class="text-center text-slate-400 text-sm mt-10 font-bold">A sala de operações está aberta. Envie uma mensagem.</div>
            </div>

            <div class="p-4 sm:p-5 bg-white border-t border-slate-200 shrink-0">
              <form @submit.prevent="enviarMensagemChat" class="flex gap-3">
                <input v-model="novaMensagemChat" type="text" placeholder="Escreva a sua mensagem..." class="flex-1 border border-slate-300 rounded-xl px-4 py-3.5 sm:py-3 text-sm outline-none focus:ring-2 focus:ring-[#035D29] placeholder-slate-400 font-medium bg-white transition-shadow shadow-sm" autocomplete="off">
                <button type="submit" :disabled="enviandoMsg || !novaMensagemChat.trim()" class="bg-[#035D29] text-white px-6 sm:px-8 font-bold rounded-xl text-sm hover:bg-[#023818] disabled:opacity-50 transition-colors shadow-md focus:outline-none">Enviar</button>
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
import axios from 'axios';
import imageCompression from 'browser-image-compression';
import { useAuthStore } from '../../stores/auth';

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
      em_transito: 'bg-purple-50 text-purple-700 border-purple-200', 
      em_auditoria: 'bg-orange-50 text-orange-800 border-orange-200', 
      entregue: 'bg-emerald-50 text-emerald-700 border-emerald-200', 
      em_disputa: 'bg-red-50 text-red-700 border-red-200',
      em_analise_gr: 'bg-amber-50 text-amber-800 border-amber-300',
      aguardando_biometria: 'bg-slate-900 text-white border-slate-900',
      rejeitado_gr: 'bg-red-50 text-red-700 border-red-200',
      pendente_correcao_gr: 'bg-amber-50 text-amber-700 border-amber-200'
  };
  return classes[status] || 'bg-slate-50 text-slate-700 border-slate-200';
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
  if (!confirm('Confirma que iniciou o deslocamento em direção ao destino?')) return;
  actionLoading.value = id;
  try {
    await axios.post(`/api/v1/motorista/cargas/${id}/iniciar-viagem`);
    alert('Viagem iniciada com sucesso. Dirija com segurança!');
    fetchMinhasCargas(); 
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

// Formatação Auxiliar
const formatData = (dataStr) => {
  if (!dataStr || typeof dataStr !== 'string') return '--';
  try {
    const datePart = dataStr.split('T')[0]; 
    if (!datePart.includes('-')) return dataStr; 
    const [year, month, day] = datePart.split('-');
    return `${day}/${month}/${year}`;
  } catch (e) { return '--'; }
};

const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const formatarMoeda = (valor) => {
  const num = parseFloat(valor);
  return isNaN(num) ? 'R$ 0,00' : moneyFormatter.format(num);
};

const formatarSnakeCase = (str) => {
  if (!str || typeof str !== 'string') return 'N/A';
  return str.replace(/_/g, ' ');
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

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Scrollbars Clean */
.scrollbar-clinical::-webkit-scrollbar { width: 5px; height: 5px;}
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>