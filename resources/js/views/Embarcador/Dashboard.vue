<template>
  <!-- TOPOLOGIA 12 COLUNAS (9 CONTEÚDO / 3 PUBLICIDADE) -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 relative max-w-screen-2xl mx-auto">
    
    <!-- COLUNA PRINCIPAL (OPERAÇÃO LOGÍSTICA) -->
    <div class="lg:col-span-9 space-y-6">
      
      <!-- HEADER MESA DE OPERAÇÕES -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-5 rounded-xl border border-surface-200 shadow-clinical-sm gap-4">
        <div>
          <h2 class="text-xl font-bold text-surface-900 tracking-tight">Painel de Controle Logístico</h2>
          <p class="text-sm text-surface-500 mt-1">Gerencie suas cargas publicadas, lances ativos, em trânsito e auditoria.</p>
        </div>
        <div class="flex gap-3 w-full sm:w-auto">
          <button @click="fetchCargas(1)" :disabled="loading" class="w-full sm:w-auto px-4 py-2 border border-surface-300 rounded-lg text-sm font-bold text-surface-700 bg-white hover:bg-surface-50 transition-colors disabled:opacity-50 flex items-center justify-center shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-surface-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            {{ loading ? 'Sincronizando...' : 'Atualizar Painel' }}
          </button>
          <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="w-full sm:w-auto px-5 py-2 bg-surface-900 text-white rounded-lg text-sm font-bold shadow-clinical-sm hover:bg-surface-800 transition-colors flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-surface-500">
            + Publicar Novo Frete
          </router-link>
        </div>
      </div>

      <!-- MURAL DE CARGAS -->
      <div class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden">
        
        <div v-if="loading && cargas?.length === 0" class="p-12 text-center text-surface-500 font-medium text-sm flex flex-col items-center">
          <svg class="w-8 h-8 animate-spin text-brand-500 mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          Sincronizando malha de operações...
        </div>

        <div v-else-if="!cargas || cargas?.length === 0" class="p-16 text-center">
          <div class="mx-auto w-16 h-16 bg-surface-50 rounded-full flex items-center justify-center mb-4 border border-surface-100">
            <svg class="w-8 h-8 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2-2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
          </div>
          <h3 class="text-base font-bold text-surface-900 tracking-tight">Nenhuma carga encontrada</h3>
          <p class="text-sm text-surface-500 mt-1">Você ainda não possui publicações nesta página.</p>
          <div class="mt-6">
            <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="inline-flex items-center rounded-lg bg-surface-900 px-5 py-2 text-sm font-bold text-white shadow-clinical-sm hover:bg-surface-800 transition-colors">
              Publicar primeira carga
            </router-link>
          </div>
        </div>

        <template v-else>
          <div class="overflow-x-auto scrollbar-clinical">
            <table class="min-w-full divide-y divide-surface-200 text-left">
              <thead class="bg-surface-50">
                <tr>
                  <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Rota / Produto</th>
                  <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Veículo</th>
                  <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Valor Oferta</th>
                  <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Status</th>
                  <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Motorista Associado</th>
                  <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-surface-500 uppercase tracking-wider">Ações</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-surface-100" :class="{ 'opacity-50 pointer-events-none': loading }">
                <tr v-for="carga in cargas" :key="carga.id" class="hover:bg-surface-50/50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-surface-900">{{ carga.cidade_origem }} → {{ carga.cidade_destino }}</div>
                    <div class="text-xs text-surface-500">{{ carga.produto }} ({{ carga.peso_kg }} kg)</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-surface-900 capitalize">{{ carga.tipo_veiculo?.replace('_', ' ') }}</div>
                    <div class="text-xs text-surface-500 mt-0.5 capitalize">{{ carga.tipo_carroceria?.replace('_', ' ') }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-surface-900 tabular-nums">
                    {{ formatMoney(carga.valor_frete) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-3 py-1 inline-flex text-[10px] font-black uppercase tracking-wider rounded-md border', getStatusClass(carga.status)]">
                      {{ carga.status?.replace('_', ' ') }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap border-l border-surface-100 bg-surface-50/30">
                    <div v-if="carga.motorista_id && carga.motorista">
                      <div class="text-sm font-bold text-surface-900 flex items-center">
                        {{ carga.motorista.user?.name || 'ID: ' + carga.motorista_id }}
                        <button @click="abrirReputacao(carga.motorista)" title="Ver Métricas Detalhadas" :class="['ml-2 px-1.5 py-0.5 rounded text-[9px] uppercase font-bold border cursor-pointer hover:shadow-clinical-sm transition-all focus:outline-none focus:ring-2 focus:ring-surface-500', getTierBadge(carga.motorista.tier_reputacao)]">
                          ⭐ {{ parseFloat(carga.motorista.score_geral || 0).toFixed(2) }} | {{ carga.motorista.tier_reputacao || 'NOVATO' }}
                        </button>
                      </div>
                      <div class="text-[10px] font-bold text-surface-400 mt-1 uppercase tracking-widest flex items-center">
                          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                          Contato Oculto (Privacidade)
                      </div>
                    </div>
                    <div v-else class="text-xs text-surface-400 italic font-medium">
                      {{ carga.candidaturas?.filter(c => c.status === 'pendente').length || 0 }} Lances Pendentes
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right space-y-2 flex flex-col items-end">
                    
                    <div class="space-x-3 flex items-center">
                      <template v-if="carga.status === 'publicada'">
                        <button v-if="carga.candidaturas && carga.candidaturas.filter(c => c.status === 'pendente').length > 0" 
                                @click="abrirModalLances(carga)" 
                                class="inline-flex items-center px-3 py-1.5 bg-brand-600 text-white font-bold text-xs rounded hover:bg-brand-700 transition-colors shadow-clinical-sm animate-pulse focus:outline-none focus:ring-2 focus:ring-brand-500">
                          Ver Lances ({{ carga.candidaturas.filter(c => c.status === 'pendente').length }})
                        </button>
                        <router-link :to="{ name: 'EmbarcadorEditarCarga', params: { id: carga.id } }" class="text-surface-600 hover:text-brand-800 font-bold transition-colors text-sm uppercase tracking-widest text-[10px] focus:outline-none">Editar</router-link>
                        <button @click="cancelarCarga(carga.id)" class="text-rose-600 hover:text-rose-800 font-bold transition-colors text-sm uppercase tracking-widest text-[10px] focus:outline-none">Cancelar</button>
                      </template>
                      
                      <template v-else-if="carga.status === 'em_auditoria'">
                        <button @click="abrirChat(carga)" class="inline-flex items-center px-3 py-1.5 bg-brand-50 border border-brand-200 text-brand-700 font-bold text-xs rounded hover:bg-brand-100 transition-colors focus:outline-none">
                          💬 Chat
                        </button>
                        <button @click="abrirModalPod(carga)" class="inline-flex items-center px-4 py-1.5 bg-amber-400 border border-amber-500 text-amber-900 font-black text-xs rounded hover:bg-amber-500 transition-colors shadow-clinical-sm animate-pulse focus:outline-none focus:ring-2 focus:ring-amber-500">
                          ⚖️ Auditar Entrega
                        </button>
                      </template>

                      <template v-else-if="['entregue', 'finalizada', 'concluida'].includes(carga.status)">
                        <button @click="abrirModalPod(carga)" class="inline-flex items-center px-3 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs rounded hover:bg-emerald-100 transition-colors focus:outline-none">
                          Ver Comprovantes
                        </button>
                      </template>
                      
                      <template v-else-if="carga.status === 'em_transito'">
                        <button @click="abrirChat(carga)" class="inline-flex items-center px-3 py-1.5 bg-brand-100 border border-brand-300 text-brand-800 font-bold text-xs rounded hover:bg-brand-200 transition-colors focus:outline-none">
                          💬 Chat da Operação
                        </button>
                        <router-link :to="{ name: 'EmbarcadorRastreamento', params: { id: carga.id } }" class="inline-flex items-center px-3 py-1.5 bg-surface-900 text-white text-xs font-bold rounded shadow-clinical-sm hover:bg-surface-800 transition-colors focus:outline-none">
                          📍 Acompanhar Rota
                        </router-link>
                      </template>
                      
                      <template v-else-if="carga.status === 'alocada'">
                        <button @click="abrirChat(carga)" class="inline-flex items-center px-3 py-1.5 bg-brand-100 border border-brand-300 text-brand-800 font-bold text-xs rounded hover:bg-brand-200 transition-colors focus:outline-none">
                          💬 Chat da Operação
                        </button>
                      </template>

                      <template v-else>
                        <span class="text-surface-400 text-[10px] font-black uppercase tracking-wider">Processando...</span>
                      </template>
                    </div>

                    <button v-if="carga.publicacao_log" @click="abrirModalContrato(carga, 'embarcador')" class="inline-flex items-center px-2 py-1 bg-surface-100 text-surface-700 border border-surface-200 font-bold text-[10px] rounded hover:bg-surface-200 transition-colors shadow-clinical-sm mt-2 focus:outline-none uppercase tracking-widest">
                      📄 Meu Certificado
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="pagination.last_page > 1" class="px-6 py-4 bg-white border-t border-surface-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-surface-700 font-medium">
              Página <span class="font-bold text-surface-900">{{ pagination.current_page }}</span> de <span class="font-bold text-surface-900">{{ pagination.last_page }}</span>
            </div>
            <div class="space-x-2">
              <button @click="fetchCargas(pagination.current_page - 1)" :disabled="pagination.current_page === 1 || loading" class="px-4 py-2 border border-surface-300 rounded-lg text-sm font-bold text-surface-700 bg-white hover:bg-surface-50 disabled:opacity-50 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-surface-500">
                Anterior
              </button>
              <button @click="fetchCargas(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page || loading" class="px-4 py-2 border border-surface-300 rounded-lg text-sm font-bold text-surface-700 bg-white hover:bg-surface-50 disabled:opacity-50 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-surface-500">
                Próxima
              </button>
            </div>
          </div>
        </template>
      </div>

    </div>

    <!-- COLUNA LATERAL DIREITA: ADTECH (3 COLUNAS) -->
    <div class="hidden lg:block lg:col-span-3">
      <div class="sticky top-6 h-[calc(100vh-140px)] w-full">
         <AdCarousel posicionamento="direita" />
      </div>
    </div>

    <!-- MODAIS GLOBAIS -->

    <!-- Lances Modal -->
    <div v-if="showModalLances" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-surface-950/80 transition-opacity backdrop-blur-sm" @click="fecharModalLances"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-surface-200">
          <div class="bg-white px-8 py-6">
            <div class="flex justify-between items-start mb-6 border-b border-surface-100 pb-4">
              <div>
                <h3 class="text-xl font-black text-surface-900 tracking-tight">Lances Disponíveis (Marketplace)</h3>
                <p class="text-sm text-surface-500 mt-1">Carga #{{ cargaSelecionada?.id }} • Escolha o motorista baseado na reputação.</p>
              </div>
              <button @click="fecharModalLances" class="text-surface-400 hover:text-surface-600 focus:outline-none">
                 <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </div>

            <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2 scrollbar-clinical">
              <div v-for="lance in candidaturasPendentes" :key="lance.id" class="p-4 border border-surface-200 rounded-lg flex justify-between items-center bg-surface-50 hover:bg-white transition-colors shadow-inner">
                <div class="flex items-center space-x-4">
                  <div class="h-12 w-12 rounded-full bg-surface-200 flex items-center justify-center overflow-hidden border-2 border-white shadow-clinical-sm shrink-0">
                    <svg class="h-6 w-6 text-surface-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-surface-900">{{ lance.motorista?.user?.name }}</h4>
                    <div class="flex items-center mt-1 space-x-2">
                      <button @click="abrirReputacao(lance.motorista)" class="text-amber-500 font-black text-xs flex items-center hover:underline focus:outline-none">
                        ⭐ {{ parseFloat(lance.motorista?.score_geral || 0).toFixed(2) }}
                      </button>
                      <span class="text-xs text-surface-500 font-medium">({{ lance.motorista?.total_viagens || 0 }} viagens)</span>
                      <span :class="['px-2 py-0.5 rounded text-[9px] uppercase font-bold border tracking-widest', getTierBadge(lance.motorista?.tier_reputacao)]">
                        Selo: {{ lance.motorista?.tier_reputacao }}
                      </span>
                    </div>
                  </div>
                </div>
                <div>
                  <button @click="aprovarMotorista(lance.id)" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 shadow-clinical-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    Aprovar Candidato
                  </button>
                </div>
              </div>
              <div v-if="candidaturasPendentes.length === 0" class="text-center py-8 text-surface-500 font-medium text-sm border-2 border-dashed border-surface-200 rounded-lg">
                Nenhum lance pendente para exibir.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- POD (Prova de Entrega) Modal -->
    <div v-if="showModalPod" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-surface-950/80 transition-opacity backdrop-blur-sm" @click="fecharModalPod"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full border border-surface-200">
          <div class="bg-white px-8 py-6">
            <div class="flex justify-between items-start mb-6 border-b border-surface-100 pb-4">
              <div>
                <h3 class="text-xl font-black text-surface-900 tracking-tight">Auditoria & Avaliação da Entrega</h3>
                <p class="text-sm text-surface-500 mt-1">Carga #{{ cargaSelecionada?.id }} • A liberação do pagamento depende da sua avaliação estrutural.</p>
              </div>
              <span v-if="cargaSelecionada?.status === 'em_auditoria'" class="bg-amber-100 text-amber-800 text-xs font-black uppercase tracking-wider px-3 py-1 rounded-full border border-amber-200 animate-pulse hidden sm:block">
                Aguardando sua Análise
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
              <div class="space-y-2">
                <h4 class="text-sm font-bold text-surface-700 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  Canhoto Físico Assinado
                </h4>
                <div class="bg-surface-50 border-2 border-dashed border-surface-300 rounded-lg h-56 flex items-center justify-center overflow-hidden group shadow-inner">
                  <img :src="cargaSelecionada?.foto_canhoto ? 'https://via.placeholder.com/600x400/1e293b/ffffff?text=SIMULAÇÃO:+CANHOTO+ASSINADO' : 'https://via.placeholder.com/600x400/f8fafc/94a3b8?text=Sem+Imagem'" class="object-cover w-full h-full transition-transform group-hover:scale-105" alt="Canhoto">
                </div>
              </div>
              <div class="space-y-2">
                <h4 class="text-sm font-bold text-surface-700 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  Estado da Carga (Destino)
                </h4>
                <div class="bg-surface-50 border-2 border-dashed border-surface-300 rounded-lg h-56 flex items-center justify-center overflow-hidden group shadow-inner">
                   <img :src="cargaSelecionada?.foto_carga ? 'https://via.placeholder.com/600x400/1e293b/ffffff?text=SIMULAÇÃO:+FOTO+DA+CARGA' : 'https://via.placeholder.com/600x400/f8fafc/94a3b8?text=Sem+Imagem'" class="object-cover w-full h-full transition-transform group-hover:scale-105" alt="Carga">
                </div>
              </div>
            </div>

            <div v-if="cargaSelecionada?.status === 'em_auditoria'" class="border-t border-surface-200 pt-6 mt-2">
               <h4 class="text-lg font-black text-surface-900 mb-4 tracking-tight">Mesa de Reputação: Avalie o Motorista</h4>
               <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-surface-50 p-5 rounded-lg border border-surface-200">
                  <div>
                    <label class="block text-xs font-bold text-surface-700 mb-2 uppercase tracking-wider">Pontualidade</label>
                    <select v-model="formAvaliacao.nota_pontualidade" class="w-full text-sm border-surface-300 rounded-md focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                      <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                      <option value="4">⭐⭐⭐⭐ Bom</option>
                      <option value="3">⭐⭐⭐ Regular</option>
                      <option value="2">⭐⭐ Atrasado</option>
                      <option value="1">⭐ Crítico</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-surface-700 mb-2 uppercase tracking-wider">Cuidado com a Carga</label>
                    <select v-model="formAvaliacao.nota_cuidado" class="w-full text-sm border-surface-300 rounded-md focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                      <option value="5">⭐⭐⭐⭐⭐ Intacta</option>
                      <option value="4">⭐⭐⭐⭐ Leve Sujeira</option>
                      <option value="3">⭐⭐⭐ Aceitável</option>
                      <option value="2">⭐⭐ Mal Acondicionada</option>
                      <option value="1">⭐ Negligência</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-surface-700 mb-2 uppercase tracking-wider">Comunicação</label>
                    <select v-model="formAvaliacao.nota_comunicacao" class="w-full text-sm border-surface-300 rounded-md focus:ring-brand-500 focus:border-brand-500 shadow-sm">
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
                   <label class="block text-xs font-bold text-surface-700 mb-2 uppercase tracking-wider">Comentários (Opcional)</label>
                   <input type="text" v-model="formAvaliacao.comentarios" maxlength="255" class="w-full text-sm border-surface-300 rounded-md focus:ring-brand-500 focus:border-brand-500 shadow-sm" placeholder="Feedback que ajuda a comunidade..." />
                 </div>
                 <div class="w-full sm:w-1/3 flex items-center p-4 bg-rose-50 border border-rose-200 rounded-lg shadow-sm">
                   <input type="checkbox" id="avariaFlag" v-model="formAvaliacao.houve_avaria" class="w-5 h-5 text-rose-600 rounded border-surface-300 focus:ring-rose-500">
                   <label for="avariaFlag" class="ml-3 text-sm font-black text-rose-900 cursor-pointer">
                     Houve Avaria / Sinistro?
                   </label>
                 </div>
               </div>
            </div>

          </div>

          <div class="bg-surface-50 px-6 sm:px-8 py-5 flex flex-col sm:flex-row items-center justify-between border-t border-surface-200 gap-4">
            <div class="text-[10px] text-surface-500 font-bold max-w-sm leading-relaxed uppercase tracking-widest text-center sm:text-left">
              ⚠️ Aviso Legal: A nota atribuída e a confirmação de recebimento são irrevogáveis. A liquidação do CIOT será executada.
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
              <template v-if="cargaSelecionada?.status === 'em_auditoria'">
                <button type="button" @click="fecharModalPod" class="w-full sm:w-auto bg-white text-surface-700 border border-surface-300 px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-surface-100 transition-colors focus:outline-none">
                  Cancelar
                </button>
                <button v-if="formAvaliacao.houve_avaria" type="button" @click="avaliarEAprovarPagamento" class="w-full sm:w-auto bg-rose-600 text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-rose-700 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                  🚨 Reter & Abrir Disputa
                </button>
                <button v-else type="button" @click="avaliarEAprovarPagamento" class="w-full sm:w-auto bg-emerald-600 text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-emerald-700 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                  ✅ Registrar & Pagar
                </button>
              </template>
              <template v-else>
                <button type="button" @click="fecharModalPod" class="w-full sm:w-auto bg-white text-surface-700 border border-surface-300 px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-surface-50 transition-colors">
                  Fechar Comprovantes
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reputação Motorista Modal -->
    <div v-if="showModalReputacao" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-surface-950/80 transition-opacity backdrop-blur-sm" @click="showModalReputacao = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-surface-200">
          <div class="bg-white px-8 py-6">
             <div class="flex justify-between items-center border-b border-surface-100 pb-4 mb-6">
                <div>
                  <h3 class="text-lg font-black text-surface-900 tracking-tight">Métricas do Motorista</h3>
                  <p class="text-sm text-surface-500">{{ motoristaSelecionado?.user?.name }}</p>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-black text-amber-500">⭐ {{ parseFloat(motoristaSelecionado?.score_geral || 0).toFixed(2) }}</div>
                  <div class="text-[9px] font-bold text-surface-400 uppercase tracking-widest">{{ motoristaSelecionado?.total_viagens || 0 }} Viagens</div>
                </div>
             </div>

             <div class="space-y-5">
                <div>
                  <div class="flex justify-between text-sm font-bold text-surface-700 mb-1">
                    <span>Pontualidade nas Coletas/Entregas</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-surface-100 rounded-full h-2.5">
                    <div class="bg-brand-600 h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm font-bold text-surface-700 mb-1">
                    <span>Cuidado com a Carga (Avarias)</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-surface-100 rounded-full h-2.5">
                    <div class="bg-emerald-500 h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm font-bold text-surface-700 mb-1">
                    <span>Comunicação e Proatividade</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-surface-100 rounded-full h-2.5">
                    <div class="bg-fuchsia-500 h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>
             </div>
          </div>
          <div class="bg-surface-50 px-6 py-4 border-t border-surface-200 text-right">
             <button @click="showModalReputacao = false" class="px-6 py-2 bg-surface-900 text-white font-bold rounded-lg hover:bg-surface-800 text-sm focus:outline-none focus:ring-2 focus:ring-surface-500">Fechar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Chat Modal -->
    <div v-if="showModalChat" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div class="fixed inset-0 bg-surface-950/80 transition-opacity backdrop-blur-sm" @click="fecharChat"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full z-10 border border-surface-200">
          
          <div class="flex flex-col h-[500px] sm:h-[600px]">
            
            <div class="bg-surface-900 px-6 py-4 flex justify-between items-center shrink-0">
              <div>
                <h3 class="text-white font-black tracking-tight">Central de Operações #{{ cargaChatAtivo?.id }}</h3>
                <p class="text-surface-400 text-xs mt-1">Fale diretamente com o Motorista.</p>
              </div>
              <button @click="fecharChat" class="text-surface-400 hover:text-white font-bold text-2xl leading-none focus:outline-none">&times;</button>
            </div>

            <div class="flex-1 p-6 overflow-y-auto scrollbar-clinical bg-surface-50 space-y-4" id="chat-messages">
               <div v-for="msg in mensagensChat" :key="msg.id" :class="['flex', msg.remetente_tipo === 'embarcador' ? 'justify-end' : 'justify-start']">
                  <div :class="['max-w-[80%] rounded-lg px-4 py-3 shadow-sm', msg.remetente_tipo === 'embarcador' ? 'bg-brand-600 text-white rounded-tr-none' : 'bg-white border border-surface-200 text-surface-800 rounded-tl-none']">
                     <div class="text-[9px] font-black uppercase tracking-widest mb-1 opacity-70">{{ msg.remetente_tipo === 'embarcador' ? 'Você' : 'Motorista' }}</div>
                     <p class="text-sm whitespace-pre-wrap">{{ msg.mensagem }}</p>
                  </div>
               </div>
               <div v-if="mensagensChat.length === 0" class="text-center text-surface-400 text-sm mt-10 italic">A sala de operações está aberta. Envie uma mensagem.</div>
            </div>

            <div class="p-4 bg-white border-t border-surface-200 shrink-0">
              <form @submit.prevent="enviarMensagemChat" class="flex gap-3">
                <input v-model.trim="novaMensagemChat" type="text" maxlength="500" placeholder="Escreva para o motorista..." class="flex-1 border border-surface-300 rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-brand-500 shadow-sm" autocomplete="off">
                <button type="submit" :disabled="enviandoMsg || !novaMensagemChat.trim()" class="bg-brand-600 text-white px-6 font-bold rounded-lg text-sm hover:bg-brand-700 disabled:opacity-50 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-brand-500">Enviar</button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Certificado Contrato Modal -->
    <div v-if="showModalContrato" class="fixed inset-0 z-modal overflow-y-auto print:static print:z-auto print:inset-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0 print:block print:p-0 print:min-h-0">
        <div class="fixed inset-0 bg-surface-950/80 transition-opacity backdrop-blur-sm print:hidden" @click="fecharModalContrato"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen print:hidden" aria-hidden="true">&#8203;</span>
        
        <div id="print-area" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full print:shadow-none print:w-full print:max-w-full print:rounded-none border border-surface-200">
          <div class="bg-white px-6 pt-6 pb-6 print:p-0">
            <div class="hidden print:block mb-8 border-b-4 border-surface-900 pb-4">
              <h1 class="text-2xl font-black text-surface-900 uppercase">123Fretei - Documento de Auditoria Logística</h1>
            </div>
            <h3 class="text-lg leading-6 font-bold text-surface-900 border-b border-surface-200 pb-2 mb-4 print:text-xl print:mt-4">
              Certificado de Publicação (Embarcador)
            </h3>
            <div class="space-y-6">
              <div class="bg-surface-900 p-4 rounded-md text-xs font-mono text-emerald-400 space-y-2 break-all shadow-inner print:bg-white print:text-black print:border print:border-surface-300">
                <p><span class="text-surface-400 font-bold print:text-surface-600">ID DA CARGA:</span> {{ cargaSelecionada?.id }}</p>
                <p><span class="text-surface-400 font-bold print:text-surface-600">CHAVE CRIPTOGRÁFICA (SHA-256):</span> {{ getLogSelecionado()?.termo_hash }}</p>
              </div>
            </div>
          </div>
          <div class="bg-surface-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-surface-200 print:hidden">
            <button type="button" @click="fecharModalContrato" class="w-full inline-flex justify-center rounded-md border border-surface-300 shadow-clinical-sm px-4 py-2 bg-white text-base font-bold text-surface-700 hover:bg-surface-50 sm:ml-3 sm:w-auto sm:text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-surface-500">Fechar</button>
            <button type="button" @click="imprimirCertificado" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-clinical-sm px-4 py-2 bg-brand-600 text-base font-bold text-white hover:bg-brand-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500">🖨️ Imprimir PDF</button>
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

// Lógica de Modais Base
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

// Helpers Visuais (Atualizados para o Design System)
const getStatusClass = (status) => {
  const classes = {
    publicada: 'bg-brand-50 text-brand-700 border-brand-200',
    alocada: 'bg-amber-50 text-amber-700 border-amber-200',
    em_transito: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
    em_auditoria: 'bg-amber-100 text-amber-800 border-amber-300',
    entregue: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    finalizada: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelada: 'bg-rose-50 text-rose-700 border-rose-200',
    em_disputa: 'bg-rose-100 text-rose-800 border-rose-300'
  };
  return classes[status] || 'bg-surface-50 text-surface-700 border-surface-200';
};

const getTierBadge = (tier) => {
  const badges = {
    novato: 'bg-surface-100 text-surface-600 border-surface-200',
    pro: 'bg-brand-100 text-brand-700 border-brand-300',
    elite: 'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-300',
    prime: 'bg-amber-100 text-amber-800 border-amber-400'
  };
  return badges[tier?.toLowerCase()] || badges.novato;
};

// Formatação Defensiva: Extração do Intl para Singleton (Evita Memory Thrashing)
const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const formatMoney = (value) => {
  const num = parseFloat(value);
  return isNaN(num) ? 'R$ 0,00' : moneyFormatter.format(num);
};

const formatData = (dataStr, showTime = false) => {
  if (!dataStr) return '--';
  try {
    const date = new Date(dataStr);
    if (isNaN(date.getTime())) return dataStr;
    return showTime ? date.toLocaleString('pt-BR') : date.toLocaleDateString('pt-BR');
  } catch (e) {
    return '--';
  }
};

const candidaturasPendentes = computed(() => {
  if (!cargaSelecionada.value || !cargaSelecionada.value.candidaturas) return [];
  return cargaSelecionada.value.candidaturas.filter(c => c.status === 'pendente');
});

// Ações (API & Modais)
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

// Chat
const abrirChat = async (carga) => {
  cargaChatAtivo.value = carga;
  showModalChat.value = true;
  await carregarMensagens(carga.id);

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
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

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