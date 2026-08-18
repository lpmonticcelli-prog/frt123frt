<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 animate-fade-in pb-8 px-2 sm:px-0">
    
    <!-- COLUNA PRINCIPAL: CENTRAL INTELIGENTE E LISTA (9 Colunas) -->
    <div class="lg:col-span-9 space-y-4 sm:space-y-6">
      
      <!-- ========================================================== -->
      <!-- TOPO INTELIGENTE: MODO DE BUSCA (FÁCIL E DIRETO) -->
      <!-- ========================================================== -->
      <div class="bg-slate-900 rounded-3xl shadow-lg p-1 overflow-hidden border border-slate-800 relative">
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-[#035D29] opacity-20 blur-3xl pointer-events-none"></div>

        <div class="bg-slate-900 rounded-[22px] p-5 sm:p-6 relative z-10">
          <h2 class="text-white text-xl sm:text-2xl font-black tracking-tight mb-1">Buscar Fretes</h2>
          <p class="text-slate-400 text-sm font-medium mb-6">Como você quer encontrar sua próxima carga?</p>

          <!-- TABS DE MODO DE BUSCA -->
          <div class="flex flex-col sm:flex-row gap-3 mb-6 bg-slate-800 p-1.5 rounded-2xl">
            <button 
              @click="modoBusca = 'gps'; acionarGpsAutomatico()" 
              :class="modoBusca === 'gps' ? 'bg-[#035D29] text-white shadow-md' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-700/50'"
              class="flex-1 py-3.5 sm:py-3 px-4 rounded-xl font-black text-sm flex justify-center items-center gap-2 transition-all focus:outline-none"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
              PERTO DE MIM
            </button>
            <button 
              @click="modoBusca = 'cidade'" 
              :class="modoBusca === 'cidade' ? 'bg-[#035D29] text-white shadow-md' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-700/50'"
              class="flex-1 py-3.5 sm:py-3 px-4 rounded-xl font-black text-sm flex justify-center items-center gap-2 transition-all focus:outline-none"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
              OUTRA CIDADE
            </button>
          </div>

          <div class="space-y-6">
            <!-- INPUT DE CIDADE (Condicional) -->
            <div v-show="modoBusca === 'cidade'" class="animate-fade-in transition-all">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Qual o estado/cidade?</label>
              <input 
                v-model="cidadeBusca" 
                type="text" 
                placeholder="Ex: Sao Paulo, curitiba, SC..." 
                class="w-full bg-slate-950 border-2 border-slate-700 rounded-xl px-5 py-4 text-white placeholder-slate-600 font-bold focus:border-[#035D29] focus:ring-0 outline-none transition-colors text-base shadow-inner"
                @keyup.enter="buscarComFiltro"
              >
            </div>

            <!-- CONTROLE DE DISTÂNCIA -->
            <div class="animate-fade-in bg-slate-800/50 p-5 rounded-2xl border border-slate-700/50">
              <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
                <label class="text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-widest flex items-center">
                  <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                  Distância Máxima
                </label>
                <div class="bg-slate-900 px-4 py-2 rounded-xl border border-slate-700 text-center sm:text-right">
                    <span class="text-white font-black text-xl tabular-nums">{{ raioBusca }}</span>
                    <span class="text-slate-500 font-bold ml-1 text-sm">KM</span>
                </div>
              </div>
              
              <div class="pt-2 pb-4">
                <input 
                  type="range" 
                  v-model="raioBusca" 
                  min="10" 
                  max="4000" 
                  step="10"
                  class="w-full h-4 bg-slate-900 rounded-full appearance-none cursor-pointer accent-[#035D29] touch-manipulation"
                  @change="buscarComFiltro"
                >
                <div class="flex justify-between text-[10px] font-black text-slate-500 mt-3 px-1 uppercase tracking-widest">
                  <span>Bem Próximo</span>
                  <span>Brasil Todo</span>
                </div>
              </div>
            </div>

            <button @click="buscarComFiltro" :disabled="loading" class="w-full bg-[#ff5500] hover:bg-[#e64d00] text-white font-black px-6 py-5 rounded-2xl shadow-[0_4px_14px_0_rgba(255,85,0,0.39)] transition-transform active:scale-[0.98] disabled:opacity-50 flex justify-center items-center text-lg focus:outline-none focus:ring-4 focus:ring-[#ff5500]/50">
              <svg v-if="loading" class="w-6 h-6 animate-spin mr-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              {{ loading ? 'Varrendo Mapa...' : 'ENCONTRAR FRETES AGORA' }}
            </button>
          </div>
        </div>
      </div>
      <!-- ========================================================== -->

      <!-- RESULTADO DA BUSCA -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="flex items-center gap-3">
            <h3 class="text-sm font-black text-slate-500 uppercase tracking-widest">Resultados</h3>
            <span v-if="!loading" class="bg-emerald-100 text-emerald-800 text-xs font-black px-2.5 py-1 rounded-lg border border-emerald-200">{{ pagination.total || cargas.length || 0 }}</span>
          </div>
          <button @click="limparFiltrosEBuscar" :disabled="loading" class="text-xs font-bold text-slate-500 hover:text-slate-800 underline focus:outline-none disabled:opacity-50">Limpar Filtros</button>
        </div>

        <!-- LOADING -->
        <div v-if="loading && (!cargas || cargas.length === 0)" class="p-12 sm:p-20 text-center text-slate-500 font-bold flex flex-col items-center">
          <svg class="w-12 h-12 animate-spin mb-4 text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <span class="text-base tracking-wide">Aguarde. Carregando oportunidades...</span>
        </div>
        
        <!-- ERRO -->
        <div v-else-if="erroApi" class="p-12 sm:p-16 text-center text-red-600 bg-red-50/50">
          <svg class="mx-auto h-16 w-16 text-red-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          <h3 class="text-lg font-black tracking-tight">Falha de Conexão</h3>
          <p class="mt-2 text-sm font-bold text-red-700">{{ erroApi }}</p>
        </div>

        <!-- VAZIO -->
        <div v-else-if="!cargas || cargas.length === 0" class="p-12 sm:p-16 text-center">
          <div class="mx-auto w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-inner">
            <svg class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          </div>
          <h3 class="text-xl font-black text-slate-900 tracking-tight">Caminhão Vazio</h3>
          <p class="mt-2 text-base text-slate-500 max-w-sm mx-auto font-medium leading-relaxed">Não achamos cargas. Tente aumentar a distância ou buscar em outra cidade.</p>
        </div>

        <!-- LISTA CARDS (UNIVERSAL PARA TODOS OS DISPOSITIVOS) -->
        <!-- Substituímos a tabela de Desktop por Cards Universais para consistência de UI/UX -->
        <template v-else>
          <div class="divide-y divide-slate-100" :class="{ 'opacity-50 pointer-events-none': loading }">
            
            <div v-for="carga in cargas" :key="carga.id" class="p-5 sm:p-6 lg:p-8 bg-white hover:bg-slate-50 transition-colors">
              
              <!-- TOPO: EMBARCADOR E VALOR -->
              <div class="flex flex-col sm:flex-row justify-between items-start mb-5 sm:mb-6 gap-4">
                <div class="min-w-0 flex-1 w-full">
                  <div class="text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Embarcador</div>
                  <div class="text-base sm:text-lg lg:text-xl font-black text-slate-900 leading-tight truncate uppercase tracking-tight" :title="carga.embarcador?.razao_social">
                    🏢 {{ carga.embarcador?.razao_social || 'Empresa Privada' }}
                  </div>
                </div>
                <div class="w-full sm:w-auto text-left sm:text-right shrink-0 bg-emerald-50 px-5 py-3 sm:py-2.5 rounded-2xl border border-emerald-100 flex flex-col justify-center">
                  <div class="text-[10px] sm:text-xs font-black text-emerald-800 uppercase tracking-widest mb-0.5">Pagamento PIX</div>
                  <div class="text-2xl sm:text-3xl font-black text-[#035D29] tabular-nums tracking-tighter">{{ formatarMoeda(carga.valor_frete) }}</div>
                </div>
              </div>

              <!-- ROTA LÓGICA (BILHETE) -->
              <div class="bg-slate-50 rounded-3xl p-5 sm:p-6 lg:p-8 border border-slate-200 mb-6 shadow-inner relative">
                <div class="flex flex-col sm:flex-row items-center sm:items-stretch justify-between gap-6 sm:gap-4 relative z-10">
                  
                  <!-- Origem -->
                  <div class="flex-1 flex flex-col items-center sm:items-start w-full text-center sm:text-left min-w-0">
                    <span class="block text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-1 sm:mb-2">Saindo de</span>
                    <span class="block text-2xl sm:text-3xl font-black text-slate-900 truncate w-full tracking-tight">{{ carga.cidade_origem }}</span>
                    <span class="block text-sm sm:text-base font-black text-slate-500 mt-1">{{ carga.uf_origem }}</span>
                  </div>
                  
                  <!-- Setinha e Distância -->
                  <div class="shrink-0 flex flex-col items-center justify-center py-2 sm:py-0 w-full sm:w-auto sm:px-8 relative">
                    <!-- Linha decorativa (Desktop) -->
                    <div class="hidden sm:block absolute top-1/2 left-0 right-0 h-0.5 bg-slate-300 border-t-2 border-dashed border-slate-300 z-0"></div>
                    
                    <div class="bg-slate-50 z-10 p-2 rounded-full border border-slate-200 shadow-sm mb-2">
                       <svg class="w-6 h-6 sm:w-8 sm:h-8 text-[#ff5500] transform rotate-90 sm:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                    <span class="text-[10px] sm:text-xs font-black text-slate-500 uppercase tracking-widest bg-slate-50 px-2 z-10 rounded">
                      {{ carga.distancia_calc ? Number(carga.distancia_calc).toFixed(0) + ' KM' : 'DIRETO' }}
                    </span>
                  </div>
                  
                  <!-- Destino -->
                  <div class="flex-1 flex flex-col items-center sm:items-end w-full text-center sm:text-right min-w-0">
                    <span class="block text-[10px] sm:text-xs font-black text-emerald-700 uppercase tracking-widest mb-1 sm:mb-2">Entregando em</span>
                    <span class="block text-2xl sm:text-3xl font-black text-[#035D29] truncate w-full tracking-tight">{{ carga.cidade_destino }}</span>
                    <span class="block text-sm sm:text-base font-black text-slate-500 mt-1">{{ carga.uf_destino }}</span>
                  </div>
                  
                </div>
              </div>

              <!-- INFORMAÇÕES ADICIONAIS -->
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
                <div class="bg-white border border-slate-200 p-4 sm:p-5 rounded-2xl shadow-sm flex flex-col justify-center">
                  <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Veículo Adequado</span>
                  <span class="font-black text-slate-800 capitalize truncate block text-sm sm:text-base">{{ formatarSnakeCase(carga.tipo_veiculo) }}</span>
                </div>
                <div class="bg-white border border-slate-200 p-4 sm:p-5 rounded-2xl shadow-sm flex flex-col justify-center">
                  <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Carroceria</span>
                  <span class="font-black text-slate-800 uppercase truncate block text-sm sm:text-base">{{ formatarSnakeCase(carga.tipo_carroceria) }}</span>
                </div>
                <div class="bg-white border border-slate-200 p-4 sm:p-5 rounded-2xl shadow-sm flex flex-col justify-center">
                  <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Carga (Produto)</span>
                  <span class="font-black text-slate-800 truncate block text-sm sm:text-base">{{ carga.produto || 'N/A' }}</span>
                </div>
                <div class="bg-white border border-slate-200 p-4 sm:p-5 rounded-2xl shadow-sm flex flex-col justify-center">
                  <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Peso Estimado</span>
                  <span class="font-black text-slate-800 truncate block text-sm sm:text-base">{{ carga.peso_kg || '0' }} kg</span>
                </div>
              </div>

              <!-- DATA E AÇÕES GIGANTES -->
              <div class="flex flex-col lg:flex-row gap-4 sm:gap-5 border-t border-slate-100 pt-6">
                <div class="w-full lg:w-1/3 bg-[#035D29]/5 p-5 rounded-2xl flex flex-col justify-center items-center text-[#035D29] font-black border border-[#035D29]/10">
                  <span class="text-[10px] uppercase tracking-widest mb-1 opacity-80">Data de Coleta</span>
                  <div class="flex items-center text-lg sm:text-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    {{ formatData(carga.data_coleta) }}
                  </div>
                </div>
                
                <div class="w-full lg:w-2/3 flex flex-col sm:flex-row gap-4">
                  <button @click="abrirModalAceite(carga)" class="flex-1 flex justify-center items-center px-6 py-5 sm:py-0 bg-[#035D29] text-white font-black text-lg sm:text-xl rounded-2xl shadow-[0_8px_20px_-4px_rgba(3,93,41,0.4)] hover:bg-[#023818] focus:outline-none transition-transform active:scale-[0.98] disabled:opacity-50">
                    ACEITAR FRETE AGORA
                  </button>
                  <button @click="abrirModalTicket(carga)" class="sm:w-auto w-full px-6 py-5 sm:py-0 border-2 border-slate-200 text-slate-600 bg-white hover:bg-slate-50 font-black rounded-2xl shadow-sm focus:outline-none flex justify-center items-center gap-2 text-sm sm:text-base transition-colors">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    Dúvidas?
                  </button>
                </div>
              </div>
              
            </div>
          </div>

          <!-- PAGINAÇÃO -->
          <div v-if="pagination.last_page > 1" class="px-5 py-6 sm:py-8 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-5 rounded-b-3xl">
            <div class="text-sm sm:text-base text-slate-500 text-center sm:text-left font-black uppercase tracking-widest">
              Página <span class="text-slate-900">{{ pagination.current_page }}</span> de <span class="text-slate-900">{{ pagination.last_page }}</span>
            </div>
            <div class="flex gap-3 w-full sm:w-auto">
              <button @click="fetchCargas(pagination.current_page - 1)" :disabled="pagination.current_page === 1 || loading" class="flex-1 sm:flex-none px-6 sm:px-8 py-4 sm:py-3.5 border-2 border-slate-200 rounded-2xl text-sm font-black text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 transition-colors shadow-sm focus:outline-none active:scale-95">
                ANTERIOR
              </button>
              <button @click="fetchCargas(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page || loading" class="flex-1 sm:flex-none px-6 sm:px-8 py-4 sm:py-3.5 border-2 border-slate-200 rounded-2xl text-sm font-black text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 transition-colors shadow-sm focus:outline-none active:scale-95">
                PRÓXIMA
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- COLUNA DIREITA: ADTECH -->
    <div class="hidden lg:block lg:col-span-3">
      <div class="sticky top-6 h-[calc(100vh-140px)]">
         <AdCarousel posicionamento="direita" />
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL DE ACEITE BLINDADO -->
    <!-- ========================================================================= -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalAceite" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Overlay escuro com blur -->
        <div class="flex items-end sm:items-center justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:p-0">
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md transition-opacity" @click="fecharModalAceite" aria-hidden="true"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]" aria-hidden="true">&#8203;</span>
          
          <!-- Box do Modal -->
          <div class="relative inline-block align-bottom sm:align-middle bg-white rounded-t-3xl sm:rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 w-full sm:w-11/12 max-w-2xl flex flex-col max-h-[95dvh]">
            
            <div class="bg-white px-6 pt-8 pb-6 sm:px-10 sm:py-10 flex-1 overflow-y-auto scrollbar-clinical">
              <div class="flex flex-col items-center text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 sm:h-24 sm:w-24 rounded-full bg-emerald-50 border-4 border-emerald-100 mb-6">
                  <svg class="h-10 w-10 sm:h-12 sm:w-12 text-[#035D29]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                </div>
                
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Confirmar Lance</h3>
                <p class="mt-3 text-sm sm:text-base text-slate-500 font-medium max-w-sm mx-auto">Revisite os dados. Se o seu lance for aprovado, o embarcador será notificado imediatamente.</p>

                <!-- CARD RESUMO NO MODAL -->
                <div class="mt-8 w-full bg-slate-50 border border-slate-200 rounded-3xl p-6 sm:p-8 text-left shadow-inner">
                  <div class="grid grid-cols-1 gap-6 text-sm">
                    
                    <div>
                      <span class="block text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Rota Logística</span>
                      <strong class="text-slate-900 text-lg sm:text-xl flex flex-wrap items-center gap-2 tracking-tight">
                        {{ cargaSelecionada?.cidade_origem }} 
                        <svg class="w-5 h-5 text-[#ff5500]" fill="currentColor" viewBox="0 0 20 20"><path d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"></path></svg>
                        {{ cargaSelecionada?.cidade_destino }}
                      </strong>
                    </div>

                    <div class="border-t border-slate-200 pt-5 flex justify-between items-center gap-4">
                      <div class="min-w-0">
                        <span class="block text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">O que vai levar</span>
                        <strong class="text-slate-900 text-base sm:text-lg font-black truncate block">{{ cargaSelecionada?.produto }}</strong>
                      </div>
                      <div class="text-right shrink-0">
                        <span class="block text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Peso</span>
                        <strong class="text-slate-900 text-base sm:text-lg font-black block">{{ cargaSelecionada?.peso_kg }} kg</strong>
                      </div>
                    </div>

                    <div class="bg-emerald-50 rounded-2xl p-5 mt-2 border border-emerald-100 flex flex-col items-center text-center">
                      <span class="block text-[10px] sm:text-xs font-black text-emerald-800 uppercase tracking-widest mb-1">Valor do Frete (PIX)</span>
                      <strong class="text-[#035D29] text-4xl sm:text-5xl font-black tabular-nums tracking-tighter">{{ formatarMoeda(cargaSelecionada?.valor_frete) }}</strong>
                    </div>

                  </div>
                </div>

                <div class="mt-6 sm:mt-8 bg-amber-50 p-5 rounded-2xl border border-amber-200 text-center w-full">
                  <p class="text-[10px] sm:text-xs text-amber-800 font-black leading-relaxed uppercase tracking-widest">
                    ⚠️ Confirmo que possuo o veículo correto e a documentação em dia para esta viagem.
                  </p>
                </div>
              </div>
            </div>
            
            <!-- BOTÕES DO MODAL -->
            <div class="bg-white px-6 sm:px-10 py-6 border-t border-slate-200 shrink-0 flex flex-col sm:flex-row-reverse gap-4">
              <button type="button" @click="confirmarAceite" :disabled="actionLoading" class="w-full flex-1 flex justify-center items-center rounded-2xl shadow-lg px-6 py-5 bg-[#035D29] text-base sm:text-lg font-black text-white hover:bg-[#023818] focus:outline-none transition-transform active:scale-[0.98] disabled:opacity-50">
                <svg v-if="actionLoading" class="w-6 h-6 animate-spin mr-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ actionLoading ? 'A ENVIAR...' : 'SIM, ENVIAR LANCE!' }}
              </button>
              <button type="button" @click="fecharModalAceite" :disabled="actionLoading" class="w-full sm:w-auto flex justify-center items-center rounded-2xl px-8 py-5 border-2 border-slate-200 bg-white text-base font-black text-slate-500 hover:bg-slate-50 focus:outline-none transition-colors">
                Cancelar
              </button>
            </div>

          </div>
        </div>
      </div>
    </transition>

    <!-- ========================================================================= -->
    <!-- MODAL DE TICKET (SAC) BLINDADO -->
    <!-- ========================================================================= -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModalTicket" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end sm:items-center justify-center min-h-[100dvh] pt-4 px-0 sm:px-4 pb-0 text-center sm:p-0">
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="fecharModalTicket" aria-hidden="true"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-[100dvh]" aria-hidden="true">&#8203;</span>

          <div class="relative inline-block align-bottom sm:align-middle bg-white rounded-t-3xl sm:rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 w-full sm:w-11/12 max-w-lg flex flex-col max-h-[90dvh]">
            <div class="bg-white px-6 sm:px-10 pt-8 pb-6 sm:py-10 flex-1 overflow-y-auto scrollbar-clinical">
              <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-5">
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Ajuda Rápida</h3>
                <span class="bg-slate-100 text-slate-600 text-[10px] px-3 py-1.5 rounded-lg border border-slate-200 font-black tabular-nums tracking-widest uppercase">Carga #{{ cargaSelecionada?.id }}</span>
              </div>
              
              <p class="text-sm sm:text-base text-slate-500 font-medium mb-8 text-left leading-relaxed">Escreva sua dúvida sobre esta carga abaixo para nossa central responder.</p>

              <form @submit.prevent="enviarTicket" class="space-y-6 sm:space-y-8 text-left" id="formTicket">
                <div>
                  <label for="ticketCategoria" class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-3">1. Sobre o que é?</label>
                  <select id="ticketCategoria" v-model="ticketForm.categoria" required class="w-full pl-5 pr-10 py-4 sm:py-5 text-base sm:text-lg border-2 border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] rounded-2xl bg-slate-50 shadow-inner font-bold text-slate-800">
                    <option value="Dúvida Técnica">Dúvida Operacional (Veículo, Peso)</option>
                    <option value="Financeiro">Dúvida Financeira (Pagamento, PIX)</option>
                    <option value="Outros">Outros / Urgências</option>
                  </select>
                </div>

                <div>
                  <label for="ticketAssunto" class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-3">2. Resumo da dúvida</label>
                  <input id="ticketAssunto" v-model.trim="ticketForm.assunto" type="text" required maxlength="100" class="w-full border-2 border-slate-300 rounded-2xl shadow-sm py-4 sm:py-5 px-5 focus:outline-none focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-lg placeholder-slate-400 font-bold" placeholder="Ex: Tem ajudante pra carga?" />
                </div>

                <div>
                  <label for="ticketMensagem" class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-3">3. Detalhes</label>
                  <textarea id="ticketMensagem" v-model.trim="ticketForm.mensagem" rows="4" required maxlength="1000" class="w-full border-2 border-slate-300 rounded-2xl shadow-sm py-4 px-5 focus:outline-none focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-lg placeholder-slate-400 resize-none font-medium" placeholder="Explique melhor a sua dúvida..."></textarea>
                </div>
              </form>
            </div>
            
            <div class="bg-white px-6 sm:px-10 py-6 border-t border-slate-200 shrink-0 flex flex-col sm:flex-row-reverse gap-4">
              <button type="submit" form="formTicket" :disabled="ticketLoading || !isFormTicketValid" class="w-full flex-1 flex justify-center items-center rounded-2xl shadow-md px-6 py-5 sm:py-4 bg-slate-900 text-base sm:text-lg font-black text-white hover:bg-slate-800 focus:outline-none transition-colors disabled:opacity-50">
                <svg v-if="ticketLoading" class="w-6 h-6 animate-spin mr-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ ticketLoading ? 'Enviando...' : 'Abrir Chamado' }}
              </button>
              <button type="button" @click="fecharModalTicket" :disabled="ticketLoading" class="w-full sm:w-auto flex justify-center items-center rounded-2xl px-8 py-5 border-2 border-slate-200 bg-white text-base font-black text-slate-500 hover:bg-slate-50 focus:outline-none transition-colors">
                Voltar
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
import AdCarousel from '../../Components/AdCarousel.vue'; 

const router = useRouter();

// ==========================================
// ESTADOS REATIVOS
// ==========================================
const cargas = ref([]);
const loading = ref(true);
const actionLoading = ref(false);
const ticketLoading = ref(false);
const erroApi = ref(null);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

// ==========================================
// CENTRAL INTELIGENTE (RADAR)
// ==========================================
const modoBusca = ref('gps'); 
const cidadeBusca = ref('');
const raioBusca = ref(150); 
let latitudeAtual = null;
let longitudeAtual = null;

const acionarGpsAutomatico = () => {
  if (modoBusca.value !== 'gps') return;
  if (!navigator.geolocation) {
    alert('O seu celular não suporta a função de GPS.');
    modoBusca.value = 'cidade';
    return;
  }
  
  loading.value = true;
  navigator.geolocation.getCurrentPosition(
    (position) => {
      latitudeAtual = position.coords.latitude;
      longitudeAtual = position.coords.longitude;
      cidadeBusca.value = ''; 
      buscarComFiltro();
    },
    (error) => {
      console.warn('GPS negado/falhou:', error);
      modoBusca.value = 'cidade'; 
      loading.value = false;
      
      // Fallbacks amigáveis
      if(error.code === 1) alert("Permita o uso do GPS no seu navegador para buscar cargas perto de você.");
      else if(error.code === 3) alert("O GPS demorou muito. Tente o modo de busca por cidade.");
    },
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
  );
};

const buscarComFiltro = async () => {
  loading.value = true;
  erroApi.value = null;
  try {
    const params = new URLSearchParams({ page: 1, raio: raioBusca.value });
    
    if (modoBusca.value === 'gps' && latitudeAtual && longitudeAtual) {
       params.append('lat', latitudeAtual);
       params.append('lng', longitudeAtual);
    } else if (modoBusca.value === 'cidade' && cidadeBusca.value.trim() !== '') {
       params.append('cidade', cidadeBusca.value.trim());
    }

    const response = await axios.get(`/api/v1/motorista/cargas/disponiveis?${params.toString()}`);
    processarRespostaCargas(response);
  } catch (error) { tratarErroCargas(error); } finally { loading.value = false; }
};

const limparFiltrosEBuscar = () => {
  modoBusca.value = 'cidade';
  cidadeBusca.value = '';
  raioBusca.value = 150;
  latitudeAtual = null;
  longitudeAtual = null;
  fetchCargas(1);
};

const processarRespostaCargas = (response) => {
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
};

const tratarErroCargas = (error) => {
  erroApi.value = error.response?.data?.message || 'A conexão falhou.';
  cargas.value = [];
};

const fetchCargas = async (page = 1) => {
  loading.value = true;
  erroApi.value = null;
  try {
    const response = await axios.get(`/api/v1/motorista/cargas/disponiveis?page=${page}`);
    processarRespostaCargas(response);
  } catch (error) { tratarErroCargas(error); } finally { loading.value = false; }
};

// ==========================================
// MODAIS E FORMULÁRIOS
// ==========================================
const showModalAceite = ref(false);
const showModalTicket = ref(false);
const cargaSelecionada = ref(null);
const ticketForm = ref({ categoria: 'Dúvida Técnica', assunto: '', mensagem: '' });
const isFormTicketValid = computed(() => ticketForm.value.assunto.trim().length > 0 && ticketForm.value.mensagem.trim().length > 0);

const abrirModalAceite = (c) => { cargaSelecionada.value = c; showModalAceite.value = true; };
const fecharModalAceite = () => { showModalAceite.value = false; cargaSelecionada.value = null; };

const confirmarAceite = async () => {
  if (!cargaSelecionada.value?.id) return;
  actionLoading.value = true;
  try {
    const res = await axios.post(`/api/v1/motorista/cargas/${cargaSelecionada.value.id}/aceitar`);
    alert(res.data.message || 'Lance enviado com sucesso!');
    fecharModalAceite();
    router.push({ name: 'MotoristaMeusFretes' }); 
  } catch (err) {
    alert(`Erro: ${err.response?.data?.error || 'Falha de comunicação'}`);
  } finally { actionLoading.value = false; }
};

const abrirModalTicket = (c) => { cargaSelecionada.value = c; ticketForm.value = { categoria: 'Dúvida Técnica', assunto: '', mensagem: '' }; showModalTicket.value = true; };
const fecharModalTicket = () => { showModalTicket.value = false; cargaSelecionada.value = null; };

const enviarTicket = async () => {
  if (!cargaSelecionada.value?.id || !isFormTicketValid.value) return;
  ticketLoading.value = true;
  try {
    await axios.post('/api/v1/suporte/tickets', { carga_id: cargaSelecionada.value.id, ...ticketForm.value });
    alert('Seu chamado foi aberto! Acompanhe na aba SAC.');
    fecharModalTicket();
  } catch (err) { alert('Falha ao abrir o chamado.'); } finally { ticketLoading.value = false; }
};

// ==========================================
// FORMATADORES
// ==========================================
const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const formatData = (dataStr) => {
  if (!dataStr) return '--';
  try { return dataStr.split('T')[0].split('-').reverse().join('/'); } catch (e) { return '--'; }
};
const formatarMoeda = (v) => isNaN(parseFloat(v)) ? 'R$ 0,00' : moneyFormatter.format(parseFloat(v));
const formatarSnakeCase = (str) => typeof str === 'string' ? str.replace(/_/g, ' ') : 'N/A';

// ==========================================
// LIFECYCLE E WEBSOCKETS
// ==========================================
onMounted(() => {
  fetchCargas();
  if (window.Echo) {
    window.Echo.channel('mural.fretes').listen('.CargaAtualizada', (e) => {
      if (!cargas.value || !e.carga) return;
      const index = cargas.value.findIndex(c => c.id === e.carga.id);
      if (e.carga.status === 'publicada') {
        index !== -1 ? cargas.value[index] = e.carga : cargas.value.unshift(e.carga);
      } else {
        if (index !== -1) cargas.value.splice(index, 1);
      }
    });
  }
});

onBeforeUnmount(() => { if (window.Echo) window.Echo.leaveChannel('mural.fretes'); });
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Scrollbars Clean */
.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px; }
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }

/* Design Bruto para o Slider de GPS do Caminhoneiro */
input[type=range] { -webkit-appearance: none; background: transparent; }
input[type=range]::-webkit-slider-thumb { 
  -webkit-appearance: none; 
  height: 32px; 
  width: 32px; 
  border-radius: 50%; 
  background: #ffffff; 
  border: 5px solid #035D29; 
  cursor: pointer; 
  margin-top: -8px; 
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4); 
}
input[type=range]::-webkit-slider-runnable-track { 
  width: 100%; 
  height: 16px; 
  cursor: pointer; 
  background: #1e293b; 
  border-radius: 10px; 
  border: 1px solid #334155; 
}
</style>