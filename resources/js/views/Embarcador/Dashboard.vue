<template>
  <!-- DIV MESTRE: O Layout já cuida do fundo, então aqui deixamos apenas a estrutura base -->
  <div class="w-full relative pb-8">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-screen-2xl mx-auto">
      
      <!-- COLUNA PRINCIPAL (OPERAÇÃO LOGÍSTICA) -->
      <div class="lg:col-span-9 space-y-6">
        
        <!-- HEADER DO PAINEL -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-6 rounded-2xl border border-slate-200 shadow-sm gap-4">
          <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Painel de Operações</h2>
            <p class="text-sm text-slate-500 mt-1 font-medium">Gerencie suas cargas, lances ativos, trânsito e auditorias.</p>
          </div>
          <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <button @click="fetchCargas(1)" :disabled="loading" class="w-full sm:w-auto px-5 py-2.5 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors disabled:opacity-50 flex items-center justify-center shadow-sm focus:outline-none">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
              {{ loading ? 'Sincronizando...' : 'Atualizar' }}
            </button>
            <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="w-full sm:w-auto px-6 py-2.5 bg-[#035D29] text-white rounded-xl text-sm font-bold shadow-md hover:bg-[#023818] transition-all flex items-center justify-center focus:outline-none">
              + Publicar Novo Frete
            </router-link>
          </div>
        </div>

        <!-- FILTRO INTELIGENTE (SMART SEARCH) -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-4 items-center transition-all focus-within:ring-2 focus-within:ring-[#035D29]/20">
           <div class="relative w-full sm:w-2/3">
              <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
              <input v-model="searchQuery" type="text" placeholder="Buscar cidade, estado, veículo ou produto..." class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] outline-none text-sm font-medium text-slate-700 transition-colors shadow-inner placeholder-slate-400">
           </div>
           <div class="w-full sm:w-1/3 relative">
              <select v-model="statusFilter" class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] outline-none text-sm font-bold text-slate-700 shadow-inner appearance-none cursor-pointer transition-colors">
                  <option value="">📊 Todos os Status</option>
                  <option value="publicada">🟢 Publicadas</option>
                  <option value="em_auditoria">🟠 Em Auditoria</option>
                  <option value="em_transito">🟣 Em Trânsito</option>
                  <option value="concluida">✅ Concluídas</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                 <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
              </div>
           </div>
        </div>

        <!-- ÁREA DE CONTEÚDO (LISTAGEM) -->
        <div class="bg-transparent lg:bg-white lg:rounded-2xl lg:shadow-sm lg:border lg:border-slate-200 lg:overflow-hidden">
          
          <!-- ESTADO: CARREGANDO -->
          <div v-if="loading && cargas?.length === 0" class="p-16 text-center text-slate-500 font-medium text-sm flex flex-col items-center bg-white rounded-2xl shadow-sm border border-slate-200 lg:border-none lg:shadow-none">
            <svg class="w-10 h-10 animate-spin text-[#ff5500] mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            Sincronizando malha de operações...
          </div>

          <!-- ESTADO: VAZIO -->
          <div v-else-if="!cargas || cargas?.length === 0" class="p-16 text-center bg-white rounded-2xl shadow-sm border border-slate-200 lg:border-none lg:shadow-none">
            <div class="mx-auto w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5 border border-slate-100 shadow-inner">
              <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2-2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">Mural Vazio</h3>
            <p class="text-slate-500 mt-2 max-w-md mx-auto">Você ainda não possui cargas ativas. Publique seu primeiro frete para conectar-se aos motoristas.</p>
            <div class="mt-8">
              <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="inline-flex items-center rounded-xl bg-[#035D29] px-8 py-3 text-sm font-bold text-white shadow-md hover:bg-[#023818] transition-all">
                Publicar primeira carga
              </router-link>
            </div>
          </div>

          <!-- ESTADO: NADA ENCONTRADO NO FILTRO -->
          <div v-else-if="cargasFiltradas.length === 0" class="p-16 text-center bg-white rounded-2xl shadow-sm border border-slate-200 lg:border-none lg:shadow-none">
            <div class="mx-auto w-16 h-16 bg-orange-50 rounded-full flex items-center justify-center mb-4 border border-orange-100 shadow-inner">
              <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Nenhuma carga encontrada</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">Não encontramos resultados para o seu filtro. Tente buscar por outra cidade ou limpar os campos.</p>
            <button @click="searchQuery = ''; statusFilter = ''" class="mt-6 text-sm font-bold text-[#035D29] hover:underline focus:outline-none">Limpar Filtros</button>
          </div>

          <!-- ESTADO: COM CARGAS -->
          <template v-else>
            <div class="w-full">
              <table class="min-w-full text-left border-collapse block lg:table">
                <thead class="bg-slate-50 hidden lg:table-header-group border-b border-slate-200">
                  <tr>
                    <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Rota / Produto</th>
                    <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Veículo</th>
                    <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Valor Financeiro</th>
                    <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Status</th>
                    <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Motorista Associado</th>
                    <th scope="col" class="px-6 py-5 text-right text-xs font-black text-slate-500 uppercase tracking-widest">Ações</th>
                  </tr>
                </thead>
                <tbody class="block lg:table-row-group divide-y-0 lg:divide-y divide-slate-100" :class="{ 'opacity-50 pointer-events-none': loading }">
                  
                  <tr v-for="carga in cargasFiltradas" :key="carga.id" class="block lg:table-row bg-white hover:bg-slate-50/80 transition-colors mb-6 lg:mb-0 rounded-2xl lg:rounded-none shadow-sm lg:shadow-none border border-slate-200 lg:border-none overflow-hidden">
                    
                    <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                      <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Rota / Produto</div>
                      <div class="text-sm font-extrabold text-slate-900 mb-1 flex items-center">
                        {{ carga.cidade_origem }} 
                        <svg class="w-4 h-4 mx-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg> 
                        {{ carga.cidade_destino }}
                      </div>
                      <div class="text-xs font-medium text-slate-500">{{ carga.produto }} ({{ carga.peso_kg }} kg)</div>
                    </td>
                    
                    <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                      <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Veículo Ideal</div>
                      <div class="text-sm font-bold text-slate-800 capitalize">{{ carga.tipo_veiculo?.replace('_', ' ') }}</div>
                      <div class="text-xs text-slate-500 mt-0.5 capitalize">{{ carga.tipo_carroceria?.replace('_', ' ') }}</div>
                    </td>
                    
                    <!-- COLUNA DE VALOR (VISÃO COMPLETA: TOTAL, ACORDADO, ANTT E PEDÁGIO) -->
                    <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                      <div class="flex lg:block justify-between items-start lg:items-center">
                        <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">Valor Financeiro</div>
                        <div class="w-full lg:w-[210px] text-right lg:text-left">
                          
                          <!-- SOMA TOTAL: FRETE + PEDÁGIO -->
                          <div class="text-[16px] font-black text-[#035D29] tabular-nums mb-1.5 border-b border-slate-200 pb-1.5" title="Total que sairá do bolso do Embarcador">
                            {{ formatMoney(Number(carga.valor_frete || 0) + Number(carga.pedagio || 0)) }}
                          </div>
                          
                          <!-- DETALHAMENTO TRIPLO -->
                          <div class="text-[9px] uppercase tracking-widest font-bold flex flex-col gap-1">
                             <div class="flex justify-between items-center text-slate-800">
                               <span>Frete Acordado:</span>
                               <span>{{ formatMoney(carga.valor_frete) }}</span>
                             </div>
                             <div class="flex justify-between items-center text-slate-500" title="Piso Oficial Calculado pelo Sistema">
                               <span>Piso ANTT (Ref):</span>
                               <!-- Se a carga foi criada antes do piso_antt, ele exibe o próprio valor do frete como fallback -->
                               <span>{{ carga.piso_antt ? formatMoney(carga.piso_antt) : formatMoney(carga.valor_frete) }}</span>
                             </div>
                             <div class="flex justify-between items-center text-slate-400">
                               <span>Vale-Pedágio:</span>
                               <span>+ {{ formatMoney(carga.pedagio || 0) }}</span>
                             </div>
                          </div>

                        </div>
                      </div>
                    </td>
                    
                    <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                      <div class="flex lg:block justify-between items-center">
                        <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</div>
                        <span :class="['px-3 py-1.5 inline-flex text-[10px] font-black uppercase tracking-widest rounded-lg border', getStatusClass(carga.status)]">
                          {{ carga.status?.replace('_', ' ') }}
                        </span>
                      </div>
                    </td>
                    
                    <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none lg:bg-slate-50/30 lg:border-l">
                      <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Motorista Associado</div>
                      <div v-if="carga.motorista_id && carga.motorista">
                        <div class="text-sm font-bold text-slate-900 flex flex-wrap items-center gap-2">
                          {{ carga.motorista.user?.name || 'ID: ' + carga.motorista_id }}
                          <button @click="abrirReputacao(carga.motorista)" title="Ver Métricas Detalhadas" :class="['px-2 py-0.5 rounded text-[10px] uppercase font-bold border cursor-pointer hover:shadow-sm transition-all', getTierBadge(carga.motorista.tier_reputacao)]">
                            ⭐ {{ parseFloat(carga.motorista.score_geral || 0).toFixed(2) }} | {{ carga.motorista.tier_reputacao || 'NOVATO' }}
                          </button>
                        </div>
                        <div class="text-[10px] font-bold text-slate-400 mt-1.5 uppercase tracking-widest flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Contato Criptografado
                        </div>
                      </div>
                      <div v-else class="text-xs text-slate-400 font-medium flex items-center h-full">
                        <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider mr-2 lg:hidden">
                          {{ carga.candidaturas?.filter(c => c.status === 'pendente').length || 0 }}
                        </span>
                        <span class="hidden lg:inline text-orange-600 font-bold mr-1">{{ carga.candidaturas?.filter(c => c.status === 'pendente').length || 0 }}</span> Lances Pendentes
                      </div>
                    </td>
                    
                    <td class="block lg:table-cell px-5 py-5 lg:px-6 lg:py-5 bg-slate-50 lg:bg-transparent rounded-b-2xl lg:rounded-none">
                      <div class="flex flex-wrap lg:justify-end gap-2 lg:gap-3 items-center w-full">
                        
                        <template v-if="carga.status === 'publicada'">
                          <button v-if="carga.candidaturas && carga.candidaturas.filter(c => c.status === 'pendente').length > 0" 
                                  @click="abrirModalLances(carga)" 
                                  class="w-full lg:w-auto inline-flex justify-center items-center px-4 py-2.5 lg:py-2 bg-[#ff5500] text-white font-bold text-xs rounded-xl lg:rounded-lg hover:bg-[#e64d00] transition-colors shadow-sm animate-pulse">
                            Ver Lances ({{ carga.candidaturas.filter(c => c.status === 'pendente').length }})
                          </button>
                          <router-link :to="{ name: 'EmbarcadorEditarCarga', params: { id: carga.id } }" class="flex-1 lg:flex-none text-center bg-white lg:bg-transparent border lg:border-none border-slate-300 px-4 py-2 lg:px-2 lg:py-1 rounded-xl lg:rounded text-slate-600 hover:text-[#035D29] font-extrabold transition-colors text-xs lg:text-[10px] uppercase tracking-widest">Editar</router-link>
                          <button @click="cancelarCarga(carga.id)" class="flex-1 lg:flex-none text-center bg-white lg:bg-transparent border lg:border-none border-slate-300 px-4 py-2 lg:px-2 lg:py-1 rounded-xl lg:rounded text-rose-600 hover:text-rose-800 font-extrabold transition-colors text-xs lg:text-[10px] uppercase tracking-widest">Cancelar</button>
                        </template>
                        
                        <template v-else-if="carga.status === 'em_auditoria'">
                          <button @click="abrirChat(carga)" class="flex-1 lg:flex-none inline-flex justify-center items-center px-4 py-2.5 lg:py-2 bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl lg:rounded-lg hover:bg-slate-200 transition-colors">
                            💬 Chat
                          </button>
                          <button @click="abrirModalPod(carga)" class="flex-1 lg:flex-none inline-flex justify-center items-center px-4 py-2.5 lg:py-2 bg-amber-400 border border-amber-500 text-amber-900 font-black text-xs rounded-xl lg:rounded-lg hover:bg-amber-500 transition-colors shadow-sm animate-pulse">
                            ⚖️ Auditar
                          </button>
                        </template>

                        <template v-else-if="['entregue', 'finalizada', 'concluida'].includes(carga.status)">
                          <button @click="abrirModalPod(carga)" class="w-full lg:w-auto inline-flex justify-center items-center px-4 py-2.5 lg:py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs rounded-xl lg:rounded-lg hover:bg-emerald-100 transition-colors">
                            Ver Comprovantes
                          </button>
                        </template>
                        
                        <template v-else-if="carga.status === 'em_transito'">
                          <button @click="abrirChat(carga)" class="flex-1 lg:flex-none inline-flex justify-center items-center px-4 py-2.5 lg:py-2 bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl lg:rounded-lg hover:bg-slate-200 transition-colors">
                            💬 Chat
                          </button>
                          <router-link :to="{ name: 'EmbarcadorRastreamento', params: { id: carga.id } }" class="flex-1 lg:flex-none inline-flex justify-center items-center px-4 py-2.5 lg:py-2 bg-slate-900 text-white text-xs font-bold rounded-xl lg:rounded-lg shadow-sm hover:bg-slate-800 transition-colors">
                            📍 Rota
                          </router-link>
                        </template>
                        
                        <template v-else-if="carga.status === 'alocada'">
                          <button @click="abrirChat(carga)" class="w-full lg:w-auto inline-flex justify-center items-center px-4 py-2.5 lg:py-2 bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl lg:rounded-lg hover:bg-slate-200 transition-colors">
                            💬 Chat da Operação
                          </button>
                        </template>

                        <template v-else>
                          <span class="w-full text-center text-slate-400 text-[10px] font-black uppercase tracking-widest py-2">Processando...</span>
                        </template>
                      </div>

                      <button v-if="carga.publicacao_log" @click="abrirModalContrato(carga, 'embarcador')" class="w-full lg:w-auto mt-4 lg:mt-2 inline-flex justify-center items-center px-4 py-2.5 lg:px-2 lg:py-1 bg-white lg:bg-slate-100 text-slate-700 border border-slate-200 font-extrabold text-xs lg:text-[10px] rounded-xl lg:rounded hover:bg-slate-50 transition-colors shadow-sm lg:shadow-none uppercase tracking-widest">
                        📄 Meu Certificado
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- PAGINAÇÃO -->
            <div v-if="pagination.last_page > 1" class="px-6 py-5 bg-white border-t lg:border border-slate-200 lg:rounded-b-2xl flex flex-col sm:flex-row items-center justify-between gap-4 mt-2 lg:mt-0">
              <div class="text-sm text-slate-500 font-medium">
                Página <span class="font-bold text-slate-900">{{ pagination.current_page }}</span> de <span class="font-bold text-slate-900">{{ pagination.last_page }}</span>
              </div>
              <div class="space-x-2 w-full sm:w-auto flex justify-between sm:justify-end">
                <button @click="fetchCargas(pagination.current_page - 1)" :disabled="pagination.current_page === 1 || loading" class="flex-1 sm:flex-none px-5 py-2 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 transition-colors shadow-sm">
                  Anterior
                </button>
                <button @click="fetchCargas(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page || loading" class="flex-1 sm:flex-none px-5 py-2 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 transition-colors shadow-sm">
                  Próxima
                </button>
              </div>
            </div>
          </template>
        </div>

      </div>

      <!-- PUBLICIDADE LATERAL -->
      <div class="hidden lg:block lg:col-span-3">
        <div class="sticky top-6 h-[calc(100vh-140px)] w-full">
           <AdCarousel posicionamento="direita" />
        </div>
      </div>

    </div>

    <!-- MODAL: LANCES DISPONÍVEIS -->
    <div v-if="showModalLances" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm" @click="fecharModalLances"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-slate-200">
          <div class="bg-white px-8 py-6">
            <div class="flex justify-between items-start mb-6 border-b border-slate-100 pb-4">
              <div>
                <h3 class="text-xl font-black text-slate-900 tracking-tight">Lances Recebidos</h3>
                <p class="text-sm text-slate-500 mt-1">Selecione o melhor motorista baseado na reputação e aceite.</p>
              </div>
              <button @click="fecharModalLances" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                 <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </div>

            <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2 scrollbar-clinical">
              <div v-for="lance in candidaturasPendentes" :key="lance.id" class="p-5 border border-slate-200 rounded-xl flex flex-col sm:flex-row justify-between sm:items-center bg-slate-50 hover:bg-white transition-colors shadow-sm gap-4">
                <div class="flex items-center space-x-4">
                  <div class="h-14 w-14 rounded-full bg-slate-200 flex items-center justify-center overflow-hidden border-2 border-white shadow-sm shrink-0">
                    <svg class="h-7 w-7 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                  </div>
                  <div>
                    <h4 class="text-base font-bold text-slate-900">{{ lance.motorista?.user?.name }}</h4>
                    <div class="flex flex-wrap items-center mt-1 gap-2">
                      <button @click="abrirReputacao(lance.motorista)" class="text-[#ff5500] font-black text-xs flex items-center hover:underline focus:outline-none">
                        ⭐ {{ parseFloat(lance.motorista?.score_geral || 0).toFixed(2) }}
                      </button>
                      <span class="text-xs text-slate-500 font-medium">({{ lance.motorista?.total_viagens || 0 }} viagens)</span>
                      <span :class="['px-2 py-0.5 rounded text-[9px] uppercase font-bold border tracking-widest', getTierBadge(lance.motorista?.tier_reputacao)]">
                        Selo: {{ lance.motorista?.tier_reputacao }}
                      </span>
                    </div>
                  </div>
                </div>
                <div>
                  <button @click="aprovarMotorista(lance.id)" class="w-full sm:w-auto bg-[#035D29] text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-[#023818] shadow-md transition-colors">
                    Aprovar Candidato
                  </button>
                </div>
              </div>
              <div v-if="candidaturasPendentes.length === 0" class="text-center py-10 text-slate-500 font-medium text-sm border-2 border-dashed border-slate-200 rounded-xl bg-slate-50">
                Nenhum lance pendente para exibir.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: AUDITORIA (POD) -->
    <div v-if="showModalPod" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm" @click="fecharModalPod"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full border border-slate-200">
          <div class="bg-white px-8 py-6">
            <div class="flex justify-between items-start mb-6 border-b border-slate-100 pb-4">
              <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">Auditoria da Entrega</h3>
                <p class="text-sm text-slate-500 mt-1">A liberação do pagamento depende da sua avaliação estrutural e validação dos comprovantes.</p>
              </div>
              <span v-if="cargaSelecionada?.status === 'em_auditoria'" class="bg-orange-100 text-orange-800 text-xs font-black uppercase tracking-widest px-4 py-1.5 rounded-full border border-orange-200 animate-pulse hidden sm:block">
                Aguardando Análise
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
              <div class="space-y-2">
                <h4 class="text-sm font-bold text-slate-700 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-[#ff5500]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  Canhoto Físico Assinado
                </h4>
                <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl h-56 flex items-center justify-center overflow-hidden group shadow-inner">
                  <img :src="cargaSelecionada?.foto_canhoto ? 'https://via.placeholder.com/600x400/0f172a/ffffff?text=SIMULAÇÃO:+CANHOTO+ASSINADO' : 'https://via.placeholder.com/600x400/f8fafc/94a3b8?text=Sem+Imagem'" class="object-cover w-full h-full transition-transform group-hover:scale-105" alt="Canhoto">
                </div>
              </div>
              <div class="space-y-2">
                <h4 class="text-sm font-bold text-slate-700 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-[#035D29]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  Estado da Carga (Destino)
                </h4>
                <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl h-56 flex items-center justify-center overflow-hidden group shadow-inner">
                   <img :src="cargaSelecionada?.foto_carga ? 'https://via.placeholder.com/600x400/0f172a/ffffff?text=SIMULAÇÃO:+FOTO+DA+CARGA' : 'https://via.placeholder.com/600x400/f8fafc/94a3b8?text=Sem+Imagem'" class="object-cover w-full h-full transition-transform group-hover:scale-105" alt="Carga">
                </div>
              </div>
            </div>

            <div v-if="cargaSelecionada?.status === 'em_auditoria'" class="border-t border-slate-200 pt-6 mt-2">
               <h4 class="text-lg font-black text-slate-900 mb-4 tracking-tight">Avaliação Definitiva do Motorista</h4>
               <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-200">
                  <div>
                    <label class="block text-xs font-black text-slate-600 mb-2 uppercase tracking-widest">Pontualidade</label>
                    <select v-model="formAvaliacao.nota_pontualidade" class="w-full text-sm border-slate-300 rounded-xl focus:ring-[#035D29] focus:border-[#035D29] shadow-sm py-2.5">
                      <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                      <option value="4">⭐⭐⭐⭐ Bom</option>
                      <option value="3">⭐⭐⭐ Regular</option>
                      <option value="2">⭐⭐ Atrasado</option>
                      <option value="1">⭐ Crítico</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-black text-slate-600 mb-2 uppercase tracking-widest">Cuidado (Avarias)</label>
                    <select v-model="formAvaliacao.nota_cuidado" class="w-full text-sm border-slate-300 rounded-xl focus:ring-[#035D29] focus:border-[#035D29] shadow-sm py-2.5">
                      <option value="5">⭐⭐⭐⭐⭐ Intacta</option>
                      <option value="4">⭐⭐⭐⭐ Leve Sujeira</option>
                      <option value="3">⭐⭐⭐ Aceitável</option>
                      <option value="2">⭐⭐ Mal Acondicionada</option>
                      <option value="1">⭐ Negligência</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-black text-slate-600 mb-2 uppercase tracking-widest">Comunicação</label>
                    <select v-model="formAvaliacao.nota_comunicacao" class="w-full text-sm border-slate-300 rounded-xl focus:ring-[#035D29] focus:border-[#035D29] shadow-sm py-2.5">
                      <option value="5">⭐⭐⭐⭐⭐ Proativo</option>
                      <option value="4">⭐⭐⭐⭐ Educado</option>
                      <option value="3">⭐⭐⭐ O Básico</option>
                      <option value="2">⭐⭐ Difícil Contato</option>
                      <option value="1">⭐ Incomunicável</option>
                    </select>
                  </div>
               </div>

               <div class="mt-4 flex flex-col sm:flex-row gap-4">
                 <div class="flex-1">
                   <label class="block text-xs font-black text-slate-600 mb-2 uppercase tracking-widest">Comentários (Opcional)</label>
                   <input type="text" v-model="formAvaliacao.comentarios" maxlength="255" class="w-full text-sm border-slate-300 rounded-xl focus:ring-[#035D29] focus:border-[#035D29] shadow-sm py-2.5" placeholder="Feedback construtivo..." />
                 </div>
                 <div class="w-full sm:w-1/3 flex items-center p-4 bg-red-50 border border-red-200 rounded-xl shadow-sm mt-6 sm:mt-0">
                   <input type="checkbox" id="avariaFlag" v-model="formAvaliacao.houve_avaria" class="w-5 h-5 text-red-600 rounded border-slate-300 focus:ring-red-500">
                   <label for="avariaFlag" class="ml-3 text-sm font-black text-red-900 cursor-pointer">
                     Houve Avaria / Sinistro?
                   </label>
                 </div>
               </div>
            </div>

          </div>

          <div class="bg-slate-50 px-6 sm:px-8 py-5 flex flex-col sm:flex-row items-center justify-between border-t border-slate-200 gap-4">
            <div class="text-[10px] text-slate-500 font-bold max-w-sm leading-relaxed uppercase tracking-widest text-center sm:text-left">
              ⚠️ Aviso Legal: A nota atribuída e a liberação de pagamento são irrevogáveis perante a lei.
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
              <template v-if="cargaSelecionada?.status === 'em_auditoria'">
                <button type="button" @click="fecharModalPod" class="w-full sm:w-auto bg-white text-slate-700 border border-slate-300 px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-slate-100 transition-colors">
                  Cancelar
                </button>
                <button v-if="formAvaliacao.houve_avaria" type="button" @click="avaliarEAprovarPagamento" class="w-full sm:w-auto bg-red-600 text-white px-8 py-2.5 rounded-xl font-bold text-sm hover:bg-red-700 transition-colors shadow-md">
                  🚨 Reter & Abrir Disputa
                </button>
                <button v-else type="button" @click="avaliarEAprovarPagamento" class="w-full sm:w-auto bg-[#035D29] text-white px-8 py-2.5 rounded-xl font-bold text-sm hover:bg-[#023818] transition-colors shadow-md">
                  ✅ Registrar & Pagar
                </button>
              </template>
              <template v-else>
                <button type="button" @click="fecharModalPod" class="w-full sm:w-auto bg-white text-slate-700 border border-slate-300 px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-slate-50 transition-colors">
                  Fechar Comprovantes
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: MÉTRICAS DO MOTORISTA -->
    <div v-if="showModalReputacao" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm" @click="showModalReputacao = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-200">
          <div class="bg-white px-8 py-6">
             <div class="flex justify-between items-center border-b border-slate-100 pb-4 mb-6">
                <div>
                  <h3 class="text-xl font-black text-slate-900 tracking-tight">Performance</h3>
                  <p class="text-sm font-bold text-slate-500 mt-1">{{ motoristaSelecionado?.user?.name }}</p>
                </div>
                <div class="text-center bg-slate-50 px-4 py-2 rounded-xl border border-slate-200">
                  <div class="text-2xl font-black text-[#ff5500]">⭐ {{ parseFloat(motoristaSelecionado?.score_geral || 0).toFixed(2) }}</div>
                  <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-1">{{ motoristaSelecionado?.total_viagens || 0 }} Viagens</div>
                </div>
             </div>

             <div class="space-y-6">
                <div>
                  <div class="flex justify-between text-xs font-black uppercase tracking-widest text-slate-700 mb-2">
                    <span>Pontualidade</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="bg-[#035D29] h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-xs font-black uppercase tracking-widest text-slate-700 mb-2">
                    <span>Cuidado com a Carga</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="bg-emerald-500 h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-xs font-black uppercase tracking-widest text-slate-700 mb-2">
                    <span>Comunicação Ativa</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="bg-blue-500 h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>
             </div>
          </div>
          <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 text-right">
             <button @click="showModalReputacao = false" class="px-6 py-2.5 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 text-sm">Fechar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: CHAT DE OPERAÇÕES -->
    <div v-if="showModalChat" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm" @click="fecharChat"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full z-10 border border-slate-200">
          
          <div class="flex flex-col h-[500px] sm:h-[600px]">
            
            <div class="bg-slate-900 px-6 py-4 flex justify-between items-center shrink-0">
              <div>
                <h3 class="text-white font-black tracking-tight text-lg">Central de Operações #{{ cargaChatAtivo?.id }}</h3>
                <p class="text-slate-400 text-xs mt-1 font-medium">Chat criptografado com o Motorista.</p>
              </div>
              <button @click="fecharChat" class="text-slate-400 hover:text-white font-bold text-3xl leading-none">&times;</button>
            </div>

            <div class="flex-1 p-6 overflow-y-auto scrollbar-clinical bg-slate-50 space-y-4" id="chat-messages">
               <div v-for="msg in mensagensChat" :key="msg.id" :class="['flex', msg.remetente_tipo === 'embarcador' ? 'justify-end' : 'justify-start']">
                  <div :class="['max-w-[80%] rounded-2xl px-5 py-3 shadow-sm', msg.remetente_tipo === 'embarcador' ? 'bg-[#035D29] text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none']">
                     <div class="text-[9px] font-black uppercase tracking-widest mb-1 opacity-70">{{ msg.remetente_tipo === 'embarcador' ? 'Você' : 'Motorista' }}</div>
                     <p class="text-sm whitespace-pre-wrap font-medium">{{ msg.mensagem }}</p>
                  </div>
               </div>
               <div v-if="mensagensChat.length === 0" class="text-center text-slate-400 text-sm mt-10 font-bold">A sala de operações está aberta. Envie a primeira mensagem.</div>
            </div>

            <div class="p-4 bg-white border-t border-slate-200 shrink-0">
              <form @submit.prevent="enviarMensagemChat" class="flex gap-3">
                <input v-model.trim="novaMensagemChat" type="text" maxlength="500" placeholder="Escreva para o motorista..." class="flex-1 border border-slate-300 rounded-xl px-5 py-3 text-sm font-medium outline-none focus:ring-2 focus:ring-[#035D29] shadow-sm" autocomplete="off">
                <button type="submit" :disabled="enviandoMsg || !novaMensagemChat.trim()" class="bg-[#035D29] text-white px-8 font-bold rounded-xl text-sm hover:bg-[#023818] disabled:opacity-50 transition-colors shadow-md">Enviar</button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: CONTRATO (CERTIFICADO) -->
    <div v-if="showModalContrato" class="fixed inset-0 z-[100] overflow-y-auto print:static print:z-auto print:inset-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0 print:block print:p-0 print:min-h-0">
        <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm print:hidden" @click="fecharModalContrato"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen print:hidden" aria-hidden="true">&#8203;</span>
        
        <div id="print-area" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full print:shadow-none print:w-full print:max-w-full print:rounded-none border border-slate-200">
          <div class="bg-white px-8 pt-8 pb-8 print:p-0">
            <div class="hidden print:block mb-8 border-b-4 border-slate-900 pb-4">
              <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tight">123Fretei - Documento de Auditoria Logística</h1>
            </div>
            <h3 class="text-xl leading-6 font-black text-slate-900 border-b border-slate-200 pb-3 mb-6 print:text-xl print:mt-4 tracking-tight">
              Certificado de Publicação Formal
            </h3>
            <div class="space-y-6">
              <div class="bg-slate-900 p-5 rounded-xl text-xs font-mono text-emerald-400 space-y-3 break-all shadow-inner print:bg-white print:text-black print:border print:border-slate-300">
                <p><span class="text-slate-400 font-bold print:text-slate-600">ID DA CARGA:</span> {{ cargaSelecionada?.id }}</p>
                <p><span class="text-slate-400 font-bold print:text-slate-600">CHAVE CRIPTOGRÁFICA (SHA-256):</span> {{ getLogSelecionado()?.termo_hash }}</p>
              </div>
            </div>
          </div>
          <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 print:hidden">
            <button type="button" @click="fecharModalContrato" class="w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-6 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-100 sm:ml-3 sm:w-auto transition-colors">Fechar</button>
            <button type="button" @click="imprimirCertificado" class="mt-3 w-full inline-flex justify-center rounded-xl shadow-md px-6 py-2.5 bg-[#035D29] text-sm font-bold text-white hover:bg-[#023818] sm:mt-0 sm:ml-3 sm:w-auto transition-colors">🖨️ Imprimir PDF</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, nextTick } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth'; 
import AdCarousel from '../../Components/AdCarousel.vue';

const auth = useAuthStore();
const cargas = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

// Variáveis para o Filtro Inteligente (Smart Search)
const searchQuery = ref('');
const statusFilter = ref('');

const showModalPod = ref(false);
const showModalContrato = ref(false); 
const showModalLances = ref(false);
const showModalReputacao = ref(false);
const showModalChat = ref(false);

const cargaSelecionada = ref(null); 
const tipoCertificadoSelecionado = ref('embarcador');
const motoristaSelecionado = ref(null);
const cargaChatAtivo = ref(null);

const mensagensChat = ref([]);
const novaMensagemChat = ref('');
const enviandoMsg = ref(false);

const formAvaliacao = ref({
  nota_pontualidade: 5,
  nota_cuidado: 5,
  nota_comunicacao: 5,
  houve_avaria: false,
  comentarios: ''
});

// MAPEAMENTO DE STATUS MODERNIZADO
const getStatusClass = (status) => {
  const classes = {
    publicada: 'bg-blue-50 text-blue-700 border-blue-200',
    alocada: 'bg-amber-50 text-amber-700 border-amber-200',
    em_transito: 'bg-purple-50 text-purple-700 border-purple-200',
    em_auditoria: 'bg-orange-50 text-orange-800 border-orange-300',
    entregue: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    finalizada: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelada: 'bg-rose-50 text-rose-700 border-rose-200',
    em_disputa: 'bg-red-100 text-red-800 border-red-300'
  };
  return classes[status] || 'bg-slate-50 text-slate-700 border-slate-200';
};

const getTierBadge = (tier) => {
  const badges = {
    novato: 'bg-slate-100 text-slate-600 border-slate-200',
    pro: 'bg-blue-100 text-blue-700 border-blue-300',
    elite: 'bg-purple-100 text-purple-700 border-purple-300',
    prime: 'bg-amber-100 text-amber-800 border-amber-400'
  };
  return badges[tier?.toLowerCase()] || badges.novato;
};

const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const formatMoney = (value) => {
  const num = parseFloat(value);
  return isNaN(num) ? 'R$ 0,00' : moneyFormatter.format(num);
};

// Computado para aplicar o Filtro Inteligente (Smart Search)
const cargasFiltradas = computed(() => {
  if (!cargas.value) return [];
  
  return cargas.value.filter(carga => {
    // 1. Filtro de Status
    if (statusFilter.value && carga.status !== statusFilter.value) {
      return false;
    }
    
    // 2. Filtro de Texto Livre
    if (searchQuery.value) {
      const term = searchQuery.value.toLowerCase();
      const searchableText = `
        ${carga.cidade_origem} ${carga.uf_origem}
        ${carga.cidade_destino} ${carga.uf_destino}
        ${carga.tipo_veiculo?.replace('_', ' ')}
        ${carga.produto}
      `.toLowerCase();
      
      if (!searchableText.includes(term)) {
        return false;
      }
    }
    
    return true;
  });
});

const candidaturasPendentes = computed(() => {
  if (!cargaSelecionada.value || !cargaSelecionada.value.candidaturas) return [];
  return cargaSelecionada.value.candidaturas.filter(c => c.status === 'pendente');
});

const fetchCargas = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/v1/embarcador/cargas?page=${page}`);
    if (response.data && response.data.data) {
      cargas.value = response.data.data;
      pagination.value = { current_page: response.data.current_page, last_page: response.data.last_page, total: response.data.total };
    } else {
      cargas.value = response.data || [];
    }
  } catch (error) {
    console.error('[API] Erro ao carregar o mural:', error);
  } finally { loading.value = false; }
};

const cancelarCarga = async (id) => {
  if (!confirm('Ação irreversível. Tem certeza que deseja cancelar esta carga?')) return;
  try {
    await axios.delete(`/api/v1/embarcador/cargas/${id}`);
    fetchCargas(pagination.value.current_page);
  } catch (error) { alert('Erro ao tentar cancelar a carga.'); }
};

const aprovarMotorista = async (candidaturaId) => {
  if (!confirm('Confirma a aprovação deste motorista para a carga? Os outros lances serão rejeitados automaticamente.')) return;
  try {
    await axios.post(`/api/v1/embarcador/cargas/${cargaSelecionada.value.id}/candidaturas/aprovar`, {
      candidatura_id: candidaturaId
    });
    alert('Motorista aprovado com sucesso! A carga entrou em processamento logístico.');
    fecharModalLances();
    fetchCargas(pagination.value.current_page);
  } catch (e) {
    alert('Falha ao aprovar candidato: ' + (e.response?.data?.error || e.message));
  }
};

const avaliarEAprovarPagamento = async () => {
  const mensagem = formAvaliacao.value.houve_avaria 
    ? 'CUIDADO: Você está marcando que houve Avaria. O pagamento será bloqueado e o motorista penalizado. Confirmar?'
    : 'Confirma o envio da avaliação e a ordem imediata de liberação do pagamento PIX para o motorista?';

  if (!confirm(mensagem)) return;
  
  try {
    await axios.post(`/api/v1/embarcador/cargas/${cargaSelecionada.value.id}/avaliar`, formAvaliacao.value);
    alert('Operação estrutural concluída e registrada no mural de auditoria.');
    fecharModalPod();
    fetchCargas(pagination.value.current_page);
  } catch (e) { 
    alert('Erro crítico ao avaliar: ' + (e.response?.data?.error || e.message)); 
  }
};

const abrirModalLances = (carga) => { cargaSelecionada.value = carga; showModalLances.value = true; };
const fecharModalLances = () => { showModalLances.value = false; if (!showModalContrato.value && !showModalPod.value) cargaSelecionada.value = null; };

const abrirModalContrato = (carga, tipo) => { cargaSelecionada.value = carga; tipoCertificadoSelecionado.value = tipo; showModalContrato.value = true; };
const fecharModalContrato = () => { showModalContrato.value = false; if (!showModalPod.value && !showModalLances.value) cargaSelecionada.value = null; };
const imprimirCertificado = () => window.print();
const getLogSelecionado = () => cargaSelecionada.value ? cargaSelecionada.value.publicacao_log : null;

const abrirModalPod = (carga) => { 
  cargaSelecionada.value = carga;
  formAvaliacao.value = { nota_pontualidade: 5, nota_cuidado: 5, nota_comunicacao: 5, houve_avaria: false, comentarios: '' };
  showModalPod.value = true; 
};
const fecharModalPod = () => { showModalPod.value = false; if(!showModalContrato.value && !showModalLances.value) cargaSelecionada.value = null; };

const abrirReputacao = (motorista) => { motoristaSelecionado.value = motorista; showModalReputacao.value = true; };

const abrirChat = async (carga) => {
  cargaChatAtivo.value = carga;
  showModalChat.value = true;
  await carregarMensagens(carga.id);

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
};

const fecharChat = () => {
  if (window.Echo && cargaChatAtivo.value) {
    window.Echo.leaveChannel(`chat.${cargaChatAtivo.value.id}`);
  }
  showModalChat.value = false;
  cargaChatAtivo.value = null;
  mensagensChat.value = [];
};

const carregarMensagens = async (cargaId) => {
  try {
    const res = await axios.get(`/api/v1/embarcador/cargas/${cargaId}/chat`);
    mensagensChat.value = res.data;
    await nextTick();
    const chatContainer = document.getElementById('chat-messages');
    if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
  } catch (e) { console.error("Erro ao carregar chat", e); }
};

const enviarMensagemChat = async () => {
  if (!novaMensagemChat.value.trim() || !cargaChatAtivo.value) return;
  enviandoMsg.value = true;
  try {
    const res = await axios.post(`/api/v1/embarcador/cargas/${cargaChatAtivo.value.id}/chat`, {
      mensagem: novaMensagemChat.value
    });
    mensagensChat.value.push(res.data);
    novaMensagemChat.value = '';
    
    await nextTick();
    const chatContainer = document.getElementById('chat-messages');
    if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
  } catch (e) {
    if (e.response?.status === 403 && e.response?.data?.error) {
      alert(e.response.data.error);
    } else {
      alert('Erro ao enviar mensagem.');
    }
  } finally {
    enviandoMsg.value = false;
  }
};

onMounted(() => {
  fetchCargas();

  if (window.Echo && auth.user?.embarcador?.id) {
    window.Echo.channel(`embarcador.${auth.user.embarcador.id}`)
      .listen('.CargaAtualizada', (e) => {
        if (!cargas.value) return;
        const index = cargas.value.findIndex(c => c.id === e.carga.id);
        if (index !== -1) cargas.value[index] = e.carga;
        else cargas.value.unshift(e.carga);
      });
  }
});

onBeforeUnmount(() => {
  if (window.Echo && auth.user?.embarcador?.id) {
    window.Echo.leaveChannel(`embarcador.${auth.user.embarcador.id}`);
  }
});
</script>

<style scoped>
.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px;}
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }

@media print {
  body * { visibility: hidden; }
  #print-area, #print-area * { visibility: visible; }
  #print-area {
    position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0;
  }
  @page { margin: 0.5cm; }
}
</style>