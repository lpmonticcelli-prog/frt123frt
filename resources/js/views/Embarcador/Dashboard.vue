<template>
  <div class="space-y-6 relative">
    <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Painel de Controle Logístico</h2>
        <p class="text-sm text-gray-500 mt-1">Gira as suas cargas publicadas, lances ativos, em trânsito e auditoria.</p>
      </div>
      <div class="flex gap-3">
        <button @click="fetchCargas(1)" :disabled="loading" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50 flex items-center shadow-sm">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          {{ loading ? 'A Sincronizar...' : 'Atualizar Painel' }}
        </button>
        <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="px-5 py-2 bg-slate-900 text-white rounded-lg text-sm font-bold shadow-sm hover:bg-slate-800 transition-colors flex items-center">
          + Publicar Novo Frete
        </router-link>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      
      <div v-if="loading && cargas?.length === 0" class="p-12 text-center text-gray-500 font-medium text-sm">
        A carregar histórico de operações e lances...
      </div>

      <div v-else-if="!cargas || cargas?.length === 0" class="p-16 text-center">
        <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2-2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
        </div>
        <h3 class="text-base font-bold text-slate-900">Nenhuma carga encontrada</h3>
        <p class="text-sm text-gray-500 mt-1">Você ainda não possui publicações nesta página.</p>
        <div class="mt-6">
          <router-link :to="{ name: 'EmbarcadorNovaCarga' }" class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 transition-colors">
            Publicar primeira carga
          </router-link>
        </div>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-left">
            <thead class="bg-slate-50">
              <tr>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Rota / Produto</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Veículo</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Valor Oferta</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Motorista Associado</th>
                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100" :class="{ 'opacity-50 pointer-events-none': loading }">
              <tr v-for="carga in cargas" :key="carga.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-bold text-slate-900">{{ carga.cidade_origem }} → {{ carga.cidade_destino }}</div>
                  <div class="text-xs text-gray-500">{{ carga.produto }} ({{ carga.peso_kg }} kg)</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900 capitalize">{{ carga.tipo_veiculo?.replace('_', ' ') }}</div>
                  <div class="text-xs text-gray-500 mt-0.5 capitalize">{{ carga.tipo_carroceria?.replace('_', ' ') }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-slate-900">
                  {{ formatMoney(carga.valor_frete) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="['px-3 py-1 inline-flex text-[10px] font-black uppercase tracking-wider rounded-md border', getStatusClass(carga.status)]">
                    {{ carga.status?.replace('_', ' ') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap border-l border-gray-100 bg-slate-50/30">
                  <div v-if="carga.motorista_id && carga.motorista">
                    <div class="text-sm font-bold text-slate-900 flex items-center">
                      {{ carga.motorista.user?.name || 'ID: ' + carga.motorista_id }}
                      <button @click="abrirReputacao(carga.motorista)" title="Ver Métricas Detalhadas" :class="['ml-2 px-1.5 py-0.5 rounded text-[9px] uppercase border cursor-pointer hover:shadow-md transition-all', getTierBadge(carga.motorista.tier_reputacao)]">
                        ⭐ {{ parseFloat(carga.motorista.score_geral || 0).toFixed(2) }} | {{ carga.motorista.tier_reputacao || 'NOVATO' }}
                      </button>
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-widest">
                       📱 Contato Oculto (Políticas de Privacidade)
                    </div>
                  </div>
                  <div v-else class="text-xs text-gray-400 italic font-medium">
                    {{ carga.candidaturas?.filter(c => c.status === 'pendente').length || 0 }} Lances Pendentes
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right space-y-2 flex flex-col items-end">
                  
                  <div class="space-x-3 flex items-center">
                    <template v-if="carga.status === 'publicada'">
                      <button v-if="carga.candidaturas && carga.candidaturas.filter(c => c.status === 'pendente').length > 0" 
                              @click="abrirModalLances(carga)" 
                              class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white font-bold text-xs rounded hover:bg-blue-700 transition-colors shadow-sm animate-pulse">
                        Ver Lances ({{ carga.candidaturas.filter(c => c.status === 'pendente').length }})
                      </button>
                      <router-link :to="{ name: 'EmbarcadorEditarCarga', params: { id: carga.id } }" class="text-slate-600 hover:text-blue-800 font-bold transition-colors text-sm">Editar</router-link>
                      <button @click="cancelarCarga(carga.id)" class="text-red-600 hover:text-red-800 font-bold transition-colors text-sm">Cancelar</button>
                    </template>
                    
                    <template v-else-if="carga.status === 'em_auditoria'">
                      <button @click="abrirChat(carga)" class="inline-flex items-center px-3 py-1.5 bg-blue-50 border border-blue-200 text-blue-700 font-bold text-xs rounded hover:bg-blue-100 transition-colors">
                        💬 Chat
                      </button>
                      <button @click="abrirModalPod(carga)" class="inline-flex items-center px-4 py-1.5 bg-yellow-400 border border-yellow-500 text-yellow-900 font-black text-xs rounded hover:bg-yellow-500 transition-colors shadow-sm animate-pulse">
                        ⚖️ Auditar Entrega
                      </button>
                    </template>

                    <template v-else-if="carga.status === 'entregue' || carga.status === 'finalizada' || carga.status === 'concluida'">
                      <button @click="abrirModalPod(carga)" class="inline-flex items-center px-3 py-1.5 bg-green-50 border border-green-200 text-green-700 font-bold text-xs rounded hover:bg-green-100 transition-colors">
                        Ver Comprovantes
                      </button>
                    </template>
                    
                    <template v-else-if="carga.status === 'em_transito'">
                      <button @click="abrirChat(carga)" class="inline-flex items-center px-3 py-1.5 bg-blue-100 border border-blue-300 text-blue-800 font-bold text-xs rounded hover:bg-blue-200 transition-colors">
                        💬 Chat da Operação
                      </button>
                      <router-link :to="{ name: 'EmbarcadorRastreamento', params: { id: carga.id } }" class="inline-flex items-center px-3 py-1.5 bg-slate-900 text-white text-xs font-bold rounded shadow-sm hover:bg-slate-800 transition-colors">
                        📍 Acompanhar Rota
                      </router-link>
                    </template>
                    
                    <template v-else-if="carga.status === 'alocada'">
                      <button @click="abrirChat(carga)" class="inline-flex items-center px-3 py-1.5 bg-blue-100 border border-blue-300 text-blue-800 font-bold text-xs rounded hover:bg-blue-200 transition-colors">
                        💬 Chat da Operação
                      </button>
                    </template>

                    <template v-else>
                      <span class="text-gray-400 text-[10px] font-black uppercase tracking-wider">Processando...</span>
                    </template>
                  </div>

                  <button v-if="carga.publicacao_log" @click="abrirModalContrato(carga, 'embarcador')" class="inline-flex items-center px-2 py-1 bg-slate-100 text-slate-700 border border-slate-200 font-bold text-[10px] rounded hover:bg-slate-200 transition-colors shadow-sm mt-2">
                    📄 Meu Certificado
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pagination.last_page > 1" class="px-6 py-4 bg-white border-t border-gray-200 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Página <span class="font-bold">{{ pagination.current_page }}</span> de <span class="font-bold">{{ pagination.last_page }}</span>
          </div>
          <div class="space-x-2">
            <button @click="fetchCargas(pagination.current_page - 1)" :disabled="pagination.current_page === 1 || loading" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">
              Anterior
            </button>
            <button @click="fetchCargas(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page || loading" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">
              Próxima
            </button>
          </div>
        </div>
      </template>
    </div>

    <div v-if="showModalLances" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-80 transition-opacity backdrop-blur-sm" @click="fecharModalLances"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-slate-200">
          <div class="bg-white px-8 py-6">
            <div class="flex justify-between items-start mb-6 border-b border-slate-100 pb-4">
              <div>
                <h3 class="text-xl font-black text-slate-900">Lances Disponíveis (Marketplace)</h3>
                <p class="text-sm text-slate-500 mt-1">Carga #{{ cargaSelecionada?.id }} • Escolha o motorista baseado na reputação.</p>
              </div>
              <button @click="fecharModalLances" class="text-slate-400 hover:text-slate-600">
                 <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </div>

            <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
              <div v-for="lance in candidaturasPendentes" :key="lance.id" class="p-4 border border-slate-200 rounded-lg flex justify-between items-center bg-slate-50 hover:bg-white transition-colors">
                <div class="flex items-center space-x-4">
                  <div class="h-12 w-12 rounded-full bg-slate-200 flex items-center justify-center overflow-hidden border-2 border-white shadow">
                    <svg class="h-6 w-6 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-slate-900">{{ lance.motorista?.user?.name }}</h4>
                    <div class="flex items-center mt-1 space-x-2">
                      <button @click="abrirReputacao(lance.motorista)" class="text-yellow-500 font-black text-xs flex items-center hover:underline">
                        ⭐ {{ parseFloat(lance.motorista?.score_geral || 0).toFixed(2) }}
                      </button>
                      <span class="text-xs text-slate-500 font-medium">({{ lance.motorista?.total_viagens || 0 }} viagens)</span>
                      <span :class="['px-2 py-0.5 rounded text-[9px] uppercase font-bold border', getTierBadge(lance.motorista?.tier_reputacao)]">
                        Selo: {{ lance.motorista?.tier_reputacao }}
                      </span>
                    </div>
                  </div>
                </div>
                <div>
                  <button @click="aprovarMotorista(lance.id)" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-green-700 shadow-sm transition-colors">
                    Aprovar Candidato
                  </button>
                </div>
              </div>
              <div v-if="candidaturasPendentes.length === 0" class="text-center py-8 text-slate-500 font-medium text-sm">
                Nenhum lance pendente para exibir.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModalPod" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-80 transition-opacity backdrop-blur-sm" @click="fecharModalPod"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full border border-slate-200">
          <div class="bg-white px-8 py-6">
            <div class="flex justify-between items-start mb-6 border-b border-slate-100 pb-4">
              <div>
                <h3 class="text-xl font-black text-slate-900">Auditoria & Avaliação da Entrega</h3>
                <p class="text-sm text-slate-500 mt-1">Carga #{{ cargaSelecionada?.id }} • A liberação do pagamento depende da sua avaliação estrutural.</p>
              </div>
              <span v-if="cargaSelecionada?.status === 'em_auditoria'" class="bg-yellow-100 text-yellow-800 text-xs font-black uppercase tracking-wider px-3 py-1 rounded-full border border-yellow-200 animate-pulse">
                Aguardando sua Análise
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
              <div class="space-y-2">
                <h4 class="text-sm font-bold text-slate-700 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  Canhoto Físico Assinado
                </h4>
                <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-lg h-56 flex items-center justify-center overflow-hidden group">
                  <img :src="cargaSelecionada?.foto_canhoto ? 'https://via.placeholder.com/600x400/1e293b/ffffff?text=SIMULAÇÃO:+CANHOTO+ASSINADO' : 'https://via.placeholder.com/600x400/f8fafc/94a3b8?text=Sem+Imagem'" class="object-cover w-full h-full transition-transform group-hover:scale-105" alt="Canhoto">
                </div>
              </div>
              <div class="space-y-2">
                <h4 class="text-sm font-bold text-slate-700 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  Estado da Carga (Destino)
                </h4>
                <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-lg h-56 flex items-center justify-center overflow-hidden group">
                   <img :src="cargaSelecionada?.foto_carga ? 'https://via.placeholder.com/600x400/1e293b/ffffff?text=SIMULAÇÃO:+FOTO+DA+CARGA' : 'https://via.placeholder.com/600x400/f8fafc/94a3b8?text=Sem+Imagem'" class="object-cover w-full h-full transition-transform group-hover:scale-105" alt="Carga">
                </div>
              </div>
            </div>

            <div v-if="cargaSelecionada?.status === 'em_auditoria'" class="border-t border-slate-200 pt-6 mt-2">
               <h4 class="text-lg font-black text-slate-900 mb-4">Mesa de Reputação: Avalie o Motorista</h4>
               <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50 p-5 rounded-lg border border-slate-200">
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2 uppercase">Pontualidade</label>
                    <select v-model="formAvaliacao.nota_pontualidade" class="w-full text-sm border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                      <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                      <option value="4">⭐⭐⭐⭐ Bom</option>
                      <option value="3">⭐⭐⭐ Regular</option>
                      <option value="2">⭐⭐ Atrasado</option>
                      <option value="1">⭐ Crítico</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2 uppercase">Cuidado com a Carga</label>
                    <select v-model="formAvaliacao.nota_cuidado" class="w-full text-sm border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                      <option value="5">⭐⭐⭐⭐⭐ Intacta</option>
                      <option value="4">⭐⭐⭐⭐ Leve Sujeira</option>
                      <option value="3">⭐⭐⭐ Aceitável</option>
                      <option value="2">⭐⭐ Mal Acondicionada</option>
                      <option value="1">⭐ Negligência</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2 uppercase">Comunicação</label>
                    <select v-model="formAvaliacao.nota_comunicacao" class="w-full text-sm border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                      <option value="5">⭐⭐⭐⭐⭐ Proativo</option>
                      <option value="4">⭐⭐⭐⭐ Educado</option>
                      <option value="3">⭐⭐⭐ O Básico</option>
                      <option value="2">⭐⭐ Difícil Contato</option>
                      <option value="1">⭐ Incomunicável</option>
                    </select>
                  </div>
               </div>

               <div class="mt-4 flex gap-4">
                 <div class="flex-1">
                   <label class="block text-xs font-bold text-slate-700 mb-2 uppercase">Comentários (Opcional)</label>
                   <input type="text" v-model="formAvaliacao.comentarios" class="w-full text-sm border-slate-300 rounded-md" placeholder="Feedback que ajuda a comunidade..." />
                 </div>
                 <div class="w-1/3 flex items-center p-4 bg-red-50 border border-red-200 rounded-lg">
                   <input type="checkbox" id="avariaFlag" v-model="formAvaliacao.houve_avaria" class="w-5 h-5 text-red-600 rounded border-gray-300 focus:ring-red-500">
                   <label for="avariaFlag" class="ml-3 text-sm font-black text-red-900 cursor-pointer">
                     Houve Avaria / Sinistro?
                   </label>
                 </div>
               </div>
            </div>

          </div>

          <div class="bg-slate-50 px-8 py-5 flex items-center justify-between border-t border-slate-200">
            <div class="text-[10px] text-slate-500 font-bold max-w-sm leading-relaxed uppercase">
              ⚠️ Aviso Legal: A nota atribuída e a confirmação de recebimento são irrevogáveis. A liquidação do CIOT será executada.
            </div>
            <div class="flex gap-3">
              <template v-if="cargaSelecionada?.status === 'em_auditoria'">
                <button type="button" @click="fecharModalPod" class="bg-white text-slate-700 border border-slate-300 px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-slate-50 transition-colors">
                  Cancelar
                </button>
                <button v-if="formAvaliacao.houve_avaria" type="button" @click="avaliarEAprovarPagamento" class="bg-red-600 text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-red-700 transition-colors shadow-md">
                  🚨 Reter Pagamento & Abrir Disputa
                </button>
                <button v-else type="button" @click="avaliarEAprovarPagamento" class="bg-green-600 text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-green-700 transition-colors shadow-md">
                  ✅ Registrar Avaliação & Pagar
                </button>
              </template>
              <template v-else>
                <button type="button" @click="fecharModalPod" class="bg-white text-slate-700 border border-slate-300 px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-slate-50 transition-colors">
                  Fechar Comprovantes
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModalReputacao" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-80 transition-opacity backdrop-blur-sm" @click="showModalReputacao = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-200">
          <div class="bg-white px-8 py-6">
             <div class="flex justify-between items-center border-b border-slate-100 pb-4 mb-6">
                <div>
                  <h3 class="text-lg font-black text-slate-900">Métricas do Motorista</h3>
                  <p class="text-sm text-slate-500">{{ motoristaSelecionado?.user?.name }}</p>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-black text-yellow-500">⭐ {{ parseFloat(motoristaSelecionado?.score_geral || 0).toFixed(2) }}</div>
                  <div class="text-[9px] font-bold text-slate-400 uppercase">{{ motoristaSelecionado?.total_viagens || 0 }} Viagens</div>
                </div>
             </div>

             <div class="space-y-5">
                <div>
                  <div class="flex justify-between text-sm font-bold text-slate-700 mb-1">
                    <span>Pontualidade nas Coletas/Entregas</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm font-bold text-slate-700 mb-1">
                    <span>Cuidado com a Carga (Avarias)</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="bg-green-500 h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm font-bold text-slate-700 mb-1">
                    <span>Comunicação e Proatividade</span>
                    <span>{{ parseFloat(motoristaSelecionado?.score_geral || 5).toFixed(1) }} / 5.0</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="bg-purple-500 h-2.5 rounded-full" :style="{ width: ((parseFloat(motoristaSelecionado?.score_geral || 5) / 5) * 100) + '%' }"></div>
                  </div>
                </div>
             </div>
          </div>
          <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 text-right">
             <button @click="showModalReputacao = false" class="px-6 py-2 bg-slate-900 text-white font-bold rounded-lg hover:bg-slate-800 text-sm">Fechar</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModalChat" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-80 transition-opacity backdrop-blur-sm" @click="fecharChat"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-slate-200 flex flex-col h-[600px]">
          
          <div class="bg-slate-900 px-6 py-4 shrink-0 flex justify-between items-center">
            <div>
              <h3 class="text-white font-black">Chat da Operação #{{ cargaChatAtivo?.id }}</h3>
              <p class="text-slate-400 text-xs mt-1">Comunicação estritamente monitorada pelo Compliance.</p>
            </div>
            <button @click="fecharChat" class="text-slate-400 hover:text-white">✕ Fechar</button>
          </div>

          <div class="flex-1 p-6 overflow-y-auto bg-slate-50 space-y-4" id="chat-messages">
             <div v-for="msg in mensagensChat" :key="msg.id" :class="['flex', msg.remetente_tipo === 'embarcador' ? 'justify-end' : 'justify-start']">
                <div :class="['max-w-[80%] rounded-lg px-4 py-3 shadow-sm', msg.remetente_tipo === 'embarcador' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none']">
                   <div class="text-[9px] font-black uppercase mb-1 opacity-70">{{ msg.remetente_tipo === 'embarcador' ? 'Você' : 'Motorista' }}</div>
                   <p class="text-sm whitespace-pre-wrap">{{ msg.mensagem }}</p>
                </div>
             </div>
             <div v-if="mensagensChat.length === 0" class="text-center text-slate-400 text-sm mt-10 italic">Nenhuma mensagem trocada nesta carga.</div>
          </div>

          <div class="p-4 bg-white border-t border-slate-200 shrink-0">
            <form @submit.prevent="enviarMensagemChat" class="flex gap-3">
              <input v-model="novaMensagemChat" type="text" placeholder="Digite sua mensagem sobre a carga..." class="flex-1 border border-slate-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" autocomplete="off">
              <button type="submit" :disabled="enviandoMsg || !novaMensagemChat.trim()" class="bg-slate-900 text-white px-6 font-bold rounded-lg text-sm hover:bg-slate-800 disabled:opacity-50 transition-colors">
                Enviar
              </button>
            </form>
            <div class="mt-2 text-[10px] text-red-600 font-bold text-center">
              ⚠️ É terminantemente PROIBIDO enviar telefone, e-mail ou WhatsApp. Infratores serão bloqueados pelo sistema.
            </div>
          </div>

        </div>
      </div>
    </div>

    <div v-if="showModalContrato" class="fixed inset-0 z-50 overflow-y-auto print:static print:z-auto print:inset-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0 print:block print:p-0 print:min-h-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity print:hidden" @click="fecharModalContrato"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen print:hidden" aria-hidden="true">&#8203;</span>
        
        <div id="print-area" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full print:shadow-none print:w-full print:max-w-full print:rounded-none border border-gray-200">
          <div class="bg-white px-6 pt-6 pb-6 print:p-0">
            <div class="hidden print:block mb-8 border-b-4 border-gray-900 pb-4">
              <h1 class="text-2xl font-black text-gray-900 uppercase">123Fretei - Documento de Auditoria Logística</h1>
            </div>
            <h3 class="text-lg leading-6 font-bold text-gray-900 border-b pb-2 mb-4 print:text-xl print:mt-4">
              Certificado de Publicação (Embarcador)
            </h3>
            <div class="space-y-6">
              <div class="bg-gray-900 p-4 rounded-md text-xs font-mono text-green-400 space-y-2 break-all shadow-inner print:bg-white print:text-black print:border print:border-gray-300">
                <p><span class="text-gray-400 font-bold print:text-gray-600">ID DA CARGA:</span> {{ cargaSelecionada?.id }}</p>
                <p><span class="text-gray-400 font-bold print:text-gray-600">CHAVE CRIPTOGRÁFICA (SHA-256):</span> {{ getLogSelecionado()?.termo_hash }}</p>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 print:hidden">
            <button type="button" @click="fecharModalContrato" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm transition-colors">Fechar</button>
            <button type="button" @click="imprimirCertificado" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">🖨️ Imprimir PDF</button>
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

// Helpers
const getStatusClass = (status) => {
  const classes = {
    publicada: 'bg-blue-50 text-blue-700 border-blue-200',
    alocada: 'bg-amber-50 text-amber-700 border-amber-200',
    em_transito: 'bg-purple-50 text-purple-700 border-purple-200',
    em_auditoria: 'bg-yellow-100 text-yellow-800 border-yellow-300',
    entregue: 'bg-green-50 text-green-700 border-green-200',
    finalizada: 'bg-green-50 text-green-700 border-green-200',
    cancelada: 'bg-red-50 text-red-700 border-red-200',
    em_disputa: 'bg-red-100 text-red-800 border-red-300'
  };
  return classes[status] || 'bg-gray-50 text-gray-700 border-gray-200';
};

const getTierBadge = (tier) => {
  const badges = {
    novato: 'bg-slate-100 text-slate-600 border-slate-200',
    pro: 'bg-blue-100 text-blue-700 border-blue-300',
    elite: 'bg-purple-100 text-purple-700 border-purple-300',
    prime: 'bg-yellow-100 text-yellow-800 border-yellow-400'
  };
  return badges[tier?.toLowerCase()] || badges.novato;
};

const formatMoney = (value) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
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
    console.error('Erro ao carregar o mural:', error);
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
};

const fecharChat = () => { showModalChat.value = false; cargaChatAtivo.value = null; mensagensChat.value = []; };

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

<style>
@media print {
  body * { visibility: hidden; }
  #print-area, #print-area * { visibility: visible; }
  #print-area {
    position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0;
  }
  @page { margin: 0.5cm; }
}
</style>