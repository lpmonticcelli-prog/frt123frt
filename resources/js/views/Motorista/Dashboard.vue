<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 animate-fade-in">
    
    <!-- COLUNA PRINCIPAL: MURAL DE FRETES (9 Colunas) -->
    <div class="lg:col-span-9 space-y-4 sm:space-y-6">
      <div class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden">
        
        <div class="px-4 py-4 sm:px-6 border-b border-surface-200 bg-surface-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <h3 class="text-base sm:text-lg font-bold text-surface-900 tracking-tight">Cargas Disponíveis na sua Região</h3>
          <button @click="fetchCargas(1)" :disabled="loading" class="w-full sm:w-auto text-sm font-bold text-brand-600 hover:text-brand-800 flex items-center justify-center gap-2 disabled:opacity-50 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 rounded-md py-2 px-4 bg-brand-50 hover:bg-brand-100 sm:bg-transparent sm:hover:bg-transparent sm:py-1">
            <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            {{ loading ? 'Sincronizando...' : 'Atualizar Mural' }}
          </button>
        </div>

        <div v-if="loading && (!cargas || cargas.length === 0)" class="p-8 text-center text-surface-500 font-medium flex flex-col items-center">
          <svg class="w-8 h-8 animate-spin mb-3 text-brand-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          Buscando fretes na malha logística...
        </div>
        
        <div v-else-if="erroApi" class="p-12 text-center text-red-600">
          <svg class="mx-auto h-12 w-12 text-red-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          <h3 class="text-sm font-bold">Falha Crítica na API de Fretes</h3>
          <p class="mt-1 text-xs font-medium">{{ erroApi }}</p>
        </div>

        <div v-else-if="!cargas || cargas.length === 0" class="p-12 text-center">
          <svg class="mx-auto h-12 w-12 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-4 text-sm font-bold text-surface-900">Nenhum frete encontrado</h3>
          <p class="mt-1 text-sm text-surface-500">Não há cargas compatíveis aguardando motorista neste momento.</p>
        </div>

        <template v-else>
          <!-- MOBILE VIEW -->
          <div class="block lg:hidden divide-y divide-surface-200" :class="{ 'opacity-50 pointer-events-none': loading }">
            <div v-for="carga in cargas" :key="`mob-${carga.id}`" class="p-4 bg-white active:bg-surface-50 transition-colors">
              <div class="flex justify-between items-start mb-2">
                <div class="pr-2 min-w-0">
                  <div class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-0.5">Embarcador</div>
                  <div class="text-sm font-bold text-surface-900 leading-tight truncate" :title="carga.embarcador?.razao_social">{{ carga.embarcador?.razao_social || 'Empresa Privada' }}</div>
                </div>
                <div class="text-right shrink-0">
                  <div class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-0.5">Oferta Líquida</div>
                  <div class="text-base font-black text-emerald-600 tabular-nums">{{ formatarMoeda(carga.valor_frete) }}</div>
                </div>
              </div>
              <div class="bg-surface-50 rounded-md p-3 border border-surface-200 mb-4 mt-2">
                <div class="flex items-center justify-between text-sm font-bold text-surface-800 gap-2">
                  <span class="truncate flex-1">{{ carga.cidade_origem }}/{{ carga.uf_origem }}</span>
                  <span class="text-surface-400 flex-shrink-0" aria-hidden="true">➔</span>
                  <span class="truncate flex-1 text-right">{{ carga.cidade_destino }}/{{ carga.uf_destino }}</span>
                </div>
                <div class="text-[11px] text-surface-600 font-medium mt-3 grid grid-cols-2 gap-2 border-t border-surface-200 pt-2">
                  <div class="min-w-0">
                    <span class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest">Mercadoria</span>
                    <span class="font-bold text-surface-800 truncate block">{{ carga.produto || 'N/A' }} <span class="text-surface-500 font-normal">({{ carga.peso_kg || '0' }}kg)</span></span>
                  </div>
                  <div class="min-w-0">
                    <span class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest">Exigência</span>
                    <span class="font-bold text-surface-800 capitalize truncate block">{{ formatarSnakeCase(carga.tipo_veiculo) }}</span>
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-3">
                <div class="text-[11px] font-bold text-surface-500 uppercase tracking-wider flex items-center">
                  <svg class="w-3.5 h-3.5 mr-1 text-surface-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  Coleta: {{ formatData(carga.data_coleta) }}
                </div>
                <div class="flex justify-end gap-2">
                  <button @click="abrirModalTicket(carga)" class="px-4 py-3 border border-surface-300 text-surface-600 bg-white hover:bg-surface-50 font-bold rounded-md shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors disabled:opacity-50" title="Dúvidas? Abra um chamado" aria-label="Abrir Chamado">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                  </button>
                  <button @click="abrirModalAceite(carga)" class="flex-1 inline-flex justify-center items-center px-4 py-3 bg-brand-600 text-white font-bold rounded-md shadow-clinical-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors disabled:opacity-50">
                    Analisar Lance
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- DESKTOP VIEW -->
          <div class="hidden lg:block w-full overflow-x-auto scrollbar-clinical" :class="{ 'opacity-50 pointer-events-none': loading }">
            <table class="min-w-full divide-y divide-surface-200">
              <thead class="bg-surface-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-[11px] font-black text-surface-500 uppercase tracking-wider">Embarcador</th>
                  <th scope="col" class="px-6 py-3 text-left text-[11px] font-black text-surface-500 uppercase tracking-wider">Rota Logística</th>
                  <th scope="col" class="px-6 py-3 text-left text-[11px] font-black text-surface-500 uppercase tracking-wider">Mercadoria / Exigência</th>
                  <th scope="col" class="px-6 py-3 text-right text-[11px] font-black text-surface-500 uppercase tracking-wider">Oferta Líquida</th>
                  <th scope="col" class="px-6 py-3 text-right text-[11px] font-black text-surface-500 uppercase tracking-wider">Ações</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-surface-200 tabular-nums">
                <tr v-for="carga in cargas" :key="`desk-${carga.id}`" class="hover:bg-surface-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-surface-900 truncate max-w-[200px]" :title="carga.embarcador?.razao_social">{{ carga.embarcador?.razao_social || 'Empresa Privada' }}</div>
                    <div class="text-[10px] font-black text-surface-500 mt-1 uppercase tracking-wider">Coleta: {{ formatData(carga.data_coleta) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-surface-900">{{ carga.cidade_origem }}/{{ carga.uf_origem }}</div>
                    <div class="text-sm text-surface-500">para {{ carga.cidade_destino }}/{{ carga.uf_destino }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-surface-900">{{ carga.produto || 'N/A' }} <span class="text-surface-400">({{ carga.peso_kg || '0' }}kg)</span></div>
                    <div class="text-xs font-bold text-surface-600 capitalize mt-0.5">{{ formatarSnakeCase(carga.tipo_veiculo) }} / {{ formatarSnakeCase(carga.tipo_carroceria) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-black text-emerald-600">
                    {{ formatarMoeda(carga.valor_frete) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="flex justify-end space-x-2">
                      <button @click="abrirModalTicket(carga)" class="inline-flex justify-center items-center px-3 py-2 border border-surface-300 text-surface-600 bg-white hover:bg-surface-50 font-bold rounded-md shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors disabled:opacity-50" title="Dúvidas? Abra um chamado" aria-label="Abrir Chamado">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                      </button>
                      <button @click="abrirModalAceite(carga)" class="inline-flex justify-center items-center px-4 py-2 bg-brand-600 text-white font-bold rounded-md shadow-clinical-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors disabled:opacity-50 text-sm">
                        Analisar Lance
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="pagination.last_page > 1" class="px-4 py-4 sm:px-6 bg-surface-50 border-t border-surface-200 flex flex-col sm:flex-row items-center justify-between gap-4 flex-shrink-0">
            <div class="text-sm text-surface-600 text-center sm:text-left tabular-nums">
              Página <span class="font-bold text-surface-900">{{ pagination.current_page }}</span> de <span class="font-bold text-surface-900">{{ pagination.last_page }}</span>
              <span class="text-surface-400 ml-1 block sm:inline">({{ pagination.total }} fretes)</span>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
              <button @click="fetchCargas(pagination.current_page - 1)" :disabled="pagination.current_page === 1 || loading" class="flex-1 sm:flex-none px-4 py-2 border border-surface-300 rounded-md text-sm font-bold text-surface-700 bg-white hover:bg-surface-50 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors shadow-clinical-sm">
                Anterior
              </button>
              <button @click="fetchCargas(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page || loading" class="flex-1 sm:flex-none px-4 py-2 border border-surface-300 rounded-md text-sm font-bold text-surface-700 bg-white hover:bg-surface-50 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors shadow-clinical-sm">
                Próxima
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- COLUNA DIREITA: ADTECH ZONA 3 (3 Colunas) -->
    <div class="hidden lg:block lg:col-span-3">
      <div class="sticky top-6 h-[calc(100vh-140px)]">
         <AdCarousel posicionamento="direita" />
      </div>
    </div>

    <!-- MODAL DE ACEITE -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalAceite" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:p-0">
          
          <div class="fixed inset-0 bg-surface-950/70 backdrop-blur-sm transition-opacity" @click="fecharModalAceite" aria-hidden="true"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]" aria-hidden="true">&#8203;</span>
          
          <div class="relative inline-block align-bottom sm:align-middle bg-white rounded-t-2xl sm:rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:max-w-lg w-full max-h-[90dvh] flex flex-col pb-safe-bottom sm:pb-0">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 flex-1 overflow-y-auto scrollbar-clinical">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-brand-50 sm:mx-0 sm:h-10 sm:w-10">
                  <svg class="h-6 w-6 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                  <h3 class="text-lg leading-6 font-black text-surface-900">Candidatura / Lance</h3>
                  <div class="mt-2 text-sm text-surface-500">Seu perfil entrará na fila de avaliação do Embarcador.</div>

                  <div class="mt-4 bg-surface-50 border border-surface-200 rounded-lg p-4 text-left">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                      <div class="col-span-2 sm:col-span-1 min-w-0">
                        <span class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest">Embarcador</span>
                        <strong class="text-surface-900 truncate block" :title="cargaSelecionada?.embarcador?.razao_social">{{ cargaSelecionada?.embarcador?.razao_social || 'Empresa Privada' }}</strong>
                      </div>
                      <div class="col-span-2 sm:col-span-1 min-w-0">
                        <span class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest">Produto</span>
                        <strong class="text-surface-900 truncate block" :title="cargaSelecionada?.produto">{{ cargaSelecionada?.produto }} ({{ cargaSelecionada?.peso_kg }}kg)</strong>
                      </div>
                      <div class="col-span-2">
                        <span class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest">Rota Logística</span>
                        <strong class="text-surface-900">{{ cargaSelecionada?.cidade_origem }}/{{ cargaSelecionada?.uf_origem }} <span class="text-surface-400 font-normal" aria-hidden="true">➔</span> {{ cargaSelecionada?.cidade_destino }}/{{ cargaSelecionada?.uf_destino }}</strong>
                      </div>
                      <div class="col-span-2 border-t border-surface-200 pt-3 mt-1">
                        <span class="block text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-1">Valor a Receber</span>
                        <strong class="text-emerald-600 text-2xl font-black tabular-nums">{{ formatarMoeda(cargaSelecionada?.valor_frete) }}</strong>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4 bg-amber-50 p-3 rounded-md text-xs text-amber-800 border border-amber-200 text-left font-medium leading-relaxed">
                    <strong>DECLARAÇÃO LEGAL:</strong> Declaro que possuo CNH e RNTRC válidos para a categoria exigida e assumo a responsabilidade civil sobre a mercadoria a partir da coleta caso o lance seja aprovado.
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-surface-50 px-4 py-4 sm:px-6 flex flex-col sm:flex-row-reverse gap-2 border-t border-surface-200 shrink-0">
              <button type="button" @click="confirmarAceite" :disabled="actionLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-transparent shadow-clinical-sm px-4 py-3 sm:py-2 bg-brand-600 text-sm font-bold text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50 transition-colors">
                <svg v-if="actionLoading" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ actionLoading ? 'Processando...' : 'Aceitar Termos e Enviar Lance' }}
              </button>
              <button type="button" @click="fecharModalAceite" :disabled="actionLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-surface-300 shadow-clinical-sm px-4 py-3 sm:py-2 bg-white text-sm font-bold text-surface-700 hover:bg-surface-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-surface-300 transition-colors">
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL DE TICKET (SAC) -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalTicket" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:p-0">
          <div class="fixed inset-0 bg-surface-950/70 backdrop-blur-sm transition-opacity" @click="fecharModalTicket" aria-hidden="true"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]" aria-hidden="true">&#8203;</span>

          <div class="relative inline-block align-bottom sm:align-middle bg-white rounded-t-2xl sm:rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:max-w-lg w-full max-h-[90dvh] flex flex-col pb-safe-bottom sm:pb-0">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 flex-1 overflow-y-auto scrollbar-clinical">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-black text-surface-900">Precisa de Ajuda?</h3>
                <span class="bg-brand-50 text-brand-700 text-[10px] px-2 py-1 rounded border border-brand-200 font-bold tabular-nums">Carga #{{ cargaSelecionada?.id }}</span>
              </div>
              
              <p class="text-sm text-surface-500 mb-4 text-left">Abra um chamado com a nossa central de atendimento para esclarecer dúvidas sobre este frete.</p>

              <form @submit.prevent="enviarTicket" class="space-y-4 text-left" id="formTicket">
                <div>
                  <label for="ticketCategoria" class="block text-sm font-bold text-surface-700">Categoria do Suporte</label>
                  <select id="ticketCategoria" v-model="ticketForm.categoria" required class="mt-1 block w-full pl-3 pr-10 py-3 sm:py-2 text-base border border-surface-300 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-md bg-white">
                    <option value="Dúvida Técnica">Dúvida Técnica (Veículo, Rota, Peso)</option>
                    <option value="Financeiro">Dúvida Financeira (Pagamento, Taxas)</option>
                    <option value="Problema no App">Erro no Aplicativo</option>
                    <option value="Outros">Outros Assuntos</option>
                  </select>
                </div>

                <div>
                  <label for="ticketAssunto" class="block text-sm font-bold text-surface-700">Resumo (Assunto)</label>
                  <input id="ticketAssunto" v-model.trim="ticketForm.assunto" type="text" required maxlength="100" class="mt-1 block w-full border border-surface-300 rounded-md shadow-sm py-3 sm:py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm placeholder-surface-400" placeholder="Ex: A altura do galpão comporta carreta LS?" />
                </div>

                <div>
                  <label for="ticketMensagem" class="block text-sm font-bold text-surface-700">Mensagem Detalhada</label>
                  <textarea id="ticketMensagem" v-model.trim="ticketForm.mensagem" rows="4" required maxlength="1000" class="mt-1 block w-full border border-surface-300 rounded-md shadow-sm py-3 sm:py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm placeholder-surface-400 resize-none" placeholder="Explique sua dúvida ao nosso time de suporte..."></textarea>
                </div>
              </form>
            </div>
            <div class="bg-surface-50 px-4 py-4 sm:px-6 flex flex-col sm:flex-row-reverse border-t border-surface-200 gap-2 shrink-0">
              <button type="submit" form="formTicket" :disabled="ticketLoading || !isFormTicketValid" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-transparent shadow-clinical-sm px-4 py-3 sm:py-2 bg-brand-600 text-sm font-bold text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50 transition-colors">
                <svg v-if="ticketLoading" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ ticketLoading ? 'Enviando...' : 'Abrir Chamado' }}
              </button>
              <button type="button" @click="fecharModalTicket" :disabled="ticketLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-surface-300 shadow-clinical-sm px-4 py-3 sm:py-2 bg-white text-sm font-bold text-surface-700 hover:bg-surface-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-surface-300 transition-colors">
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AdCarousel from '../../Components/AdCarousel.vue'; // CORREÇÃO DE CASE SENSITIVITY

const router = useRouter();

// Estados Reativos
const cargas = ref([]);
const loading = ref(true);
const actionLoading = ref(false);
const ticketLoading = ref(false);
const erroApi = ref(null);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

// Controle de Modais
const showModalAceite = ref(false);
const showModalTicket = ref(false);
const cargaSelecionada = ref(null);

// Formulários
const ticketForm = ref({ categoria: 'Dúvida Técnica', assunto: '', mensagem: '' });

// Computeds
const isFormTicketValid = computed(() => {
  return ticketForm.value.assunto.trim().length > 0 && ticketForm.value.mensagem.trim().length > 0;
});

// Formatadores Defensivos de Alta Performance (Evitando instanciar no loop)
const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

const formatData = (dataStr) => {
  if (!dataStr || typeof dataStr !== 'string') return '--';
  try {
    const datePart = dataStr.split('T')[0]; 
    if (!datePart.includes('-')) return dataStr; 
    const [year, month, day] = datePart.split('-');
    return `${day}/${month}/${year}`;
  } catch (e) {
    return '--';
  }
};

const formatarMoeda = (valor) => {
  const num = parseFloat(valor);
  return isNaN(num) ? 'R$ 0,00' : moneyFormatter.format(num);
};

const formatarSnakeCase = (str) => {
  if (!str || typeof str !== 'string') return 'N/A';
  return str.replace(/_/g, ' ');
};

// Requisições à API
const fetchCargas = async (page = 1) => {
  loading.value = true;
  erroApi.value = null;
  try {
    const response = await axios.get(`/api/v1/motorista/cargas/disponiveis?page=${page}`);
    if (response.data && response.data.data) {
      cargas.value = response.data.data;
      pagination.value = { 
        current_page: response.data.current_page || 1, 
        last_page: response.data.last_page || 1, 
        total: response.data.total || 0 
      };
    } else {
      cargas.value = Array.isArray(response.data) ? response.data : [];
    }
  } catch (error) {
    console.error('[API] Falha ao carregar a malha logística:', error);
    erroApi.value = error.response?.data?.message || 'Conexão com o servidor de rotas foi interrompida.';
    cargas.value = [];
  } finally {
    loading.value = false;
  }
};

// Ações Logísticas
const abrirModalAceite = (carga) => { 
  if (!carga || !carga.id) return;
  cargaSelecionada.value = carga; 
  showModalAceite.value = true; 
};

const fecharModalAceite = () => { 
  showModalAceite.value = false; 
  cargaSelecionada.value = null; 
};

const confirmarAceite = async () => {
  if (!cargaSelecionada.value?.id) return;
  actionLoading.value = true;
  try {
    const response = await axios.post(`/api/v1/motorista/cargas/${cargaSelecionada.value.id}/aceitar`);
    alert(response.data.message || 'Lance enviado com sucesso!');
    fecharModalAceite();
    router.push({ name: 'MotoristaMeusFretes' }); 
  } catch (error) {
    const status = error.response?.status;
    const errorMsg = error.response?.data?.error || error.response?.data?.message || 'Erro crítico de conexão com a infraestrutura.';
    
    if (status === 409) {
      alert(`Status: ${errorMsg}`);
      fecharModalAceite();
      fetchCargas(pagination.value.current_page);
    } else {
      alert(`[FALHA] ${errorMsg}`);
    }
  } finally {
    actionLoading.value = false;
  }
};

// Ações de Suporte (SAC)
const abrirModalTicket = (carga) => { 
  if (!carga || !carga.id) return;
  cargaSelecionada.value = carga; 
  ticketForm.value = { categoria: 'Dúvida Técnica', assunto: '', mensagem: '' }; 
  showModalTicket.value = true; 
};

const fecharModalTicket = () => { 
  showModalTicket.value = false; 
  cargaSelecionada.value = null; 
};

const enviarTicket = async () => {
  if (!cargaSelecionada.value?.id || !isFormTicketValid.value) return;
  ticketLoading.value = true;
  
  try {
    const payload = { 
      carga_id: cargaSelecionada.value.id,
      categoria: ticketForm.value.categoria, 
      assunto: ticketForm.value.assunto, 
      mensagem: ticketForm.value.mensagem 
    };
    
    const response = await axios.post('/api/v1/suporte/tickets', payload);
    alert(response.data.message || 'Chamado registrado. Acompanhe na central de suporte.');
    fecharModalTicket();
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'A comunicação com o Centro de Comando falhou.';
    alert(`[SUPORTE] ${errorMsg}`);
  } finally {
    ticketLoading.value = false;
  }
};

// Ciclo de Vida e WebSockets
onMounted(() => {
  fetchCargas();
  if (window.Echo) {
    window.Echo.channel('mural.fretes')
      .listen('.CargaAtualizada', (e) => {
        if (!cargas.value || !e.carga) return;
        
        const index = cargas.value.findIndex(c => c.id === e.carga.id);
        if (e.carga.status === 'publicada') {
          if (index !== -1) {
            cargas.value[index] = e.carga;
          } else {
            cargas.value.unshift(e.carga);
          }
        } else {
          if (index !== -1) cargas.value.splice(index, 1);
        }
      });
  }
});

onBeforeUnmount(() => {
  if (window.Echo) window.Echo.leaveChannel('mural.fretes');
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.scrollbar-clinical::-webkit-scrollbar { width: 6px; }
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>