<template>
  <!-- Usa 100dvh para corrigir o bug da barra do Safari no mobile -->
  <div class="w-full relative min-h-screen min-h-[100dvh] bg-slate-50 pb-20 sm:pb-12 pt-4 sm:pt-8 px-4 sm:px-6 overflow-x-hidden">
    <div class="max-w-4xl mx-auto">
      
      <!-- HEADER RESPONSIVO -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-5 sm:p-6 rounded-t-2xl border-b border-slate-200 shadow-sm gap-4 sm:gap-0">
        <div>
          <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Publicar Novo Frete</h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Siga as etapas para registrar sua carga.</p>
        </div>
        <button @click="$router.push({ name: 'EmbarcadorDashboard' })" class="w-full sm:w-auto text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-200 rounded-xl px-4 py-3 sm:py-2 border border-slate-200 hover:bg-slate-50 bg-white shadow-sm sm:shadow-none text-center">
          Cancelar Publicação
        </button>
      </div>

      <div class="bg-white rounded-b-2xl shadow-sm border border-t-0 border-slate-200 overflow-hidden">
        
        <!-- ALERTA CONTA PENDENTE -->
        <div v-if="auth.user?.status === 'pending'" class="bg-amber-50 border-l-4 border-amber-500 p-4 sm:p-5 m-4 sm:m-6 rounded-r-xl shadow-sm">
          <div class="flex items-start">
            <div class="flex-shrink-0 mt-0.5">
              <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-xs sm:text-sm text-amber-800 font-bold leading-relaxed">
                Conta em análise pendente. O rascunho está liberado, mas a publicação exige aprovação.
              </p>
            </div>
          </div>
        </div>

        <div class="p-4 sm:p-8">
          
          <!-- STEPPER RESPONSIVO (BARRA DE PROGRESSO) -->
          <div class="mb-8 sm:mb-10 relative px-2 sm:px-0">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1.5 bg-slate-100 rounded-full z-0"></div>
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 h-1.5 bg-[#035D29] rounded-full z-0 transition-all duration-500 ease-in-out" :style="{ width: ((currentStep - 1) / 2) * 100 + '%' }"></div>
            
            <div class="relative z-10 flex justify-between items-center w-full">
              <div class="flex flex-col items-center cursor-pointer touch-manipulation" @click="currentStep = 1">
                <div :class="['w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm border-[3px] sm:border-4 transition-colors duration-300', currentStep >= 1 ? 'bg-[#035D29] border-[#ecfdf5] text-white' : 'bg-white border-slate-100 text-slate-400']">1</div>
                <span :class="['mt-2 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-center', currentStep >= 1 ? 'text-[#035D29]' : 'text-slate-400']">O Veículo</span>
              </div>
              <div class="flex flex-col items-center">
                <div :class="['w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm border-[3px] sm:border-4 transition-colors duration-300', currentStep >= 2 ? 'bg-[#035D29] border-[#ecfdf5] text-white' : 'bg-white border-slate-100 text-slate-400']">2</div>
                <span :class="['mt-2 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-center', currentStep >= 2 ? 'text-[#035D29]' : 'text-slate-400']">A Rota</span>
              </div>
              <div class="flex flex-col items-center">
                <div :class="['w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm border-[3px] sm:border-4 transition-colors duration-300', currentStep >= 3 ? 'bg-[#035D29] border-[#ecfdf5] text-white' : 'bg-white border-slate-100 text-slate-400']">3</div>
                <span :class="['mt-2 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-center', currentStep >= 3 ? 'text-[#035D29]' : 'text-slate-400']">A Oferta</span>
              </div>
            </div>
          </div>

          <form @submit.prevent="submitCarga">
            
            <!-- ================= ETAPA 1: CARGA E VEÍCULO ================= -->
            <div v-show="currentStep === 1">
              
              <!-- RESUMO DA ETAPA 1 NO MOBILE (Se já escolheu) -->
              <div v-if="form.tipo_veiculo" class="flex flex-col sm:flex-row gap-3 mb-6 animate-fade-in">
                <div class="flex-1 bg-slate-50 border border-slate-200 p-3 sm:p-4 rounded-xl shadow-sm flex justify-between items-center">
                  <div>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">1. Veículo</span>
                    <p class="font-bold text-slate-800 text-sm">{{ getVeiculoNome(form.tipo_veiculo) }}</p>
                  </div>
                  <button type="button" @click="resetVeiculo" class="text-xs font-bold text-[#035D29] hover:underline p-2 -mr-2">Alterar</button>
                </div>

                <div v-if="form.tipo_carroceria" class="flex-1 bg-slate-50 border border-slate-200 p-3 sm:p-4 rounded-xl shadow-sm flex justify-between items-center animate-fade-in">
                  <div>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">2. Carroceria</span>
                    <p class="font-bold text-slate-800 text-sm">{{ getCarroceriaNome(form.tipo_carroceria) }}</p>
                  </div>
                  <button type="button" @click="form.tipo_carroceria = ''" class="text-xs font-bold text-[#035D29] hover:underline p-2 -mr-2">Alterar</button>
                </div>
              </div>

              <!-- 1.1 CATEGORIA DO VEÍCULO -->
              <div v-if="!form.tipo_veiculo" class="animate-fade-in">
                <h3 class="text-[11px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Selecione a Categoria do Veículo <span class="text-red-500">*</span></h3>
                <div class="grid grid-cols-1 min-[400px]:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                  <div 
                    v-for="v in opcoesVeiculos" :key="v.id"
                    @click="form.tipo_veiculo = v.id"
                    class="relative cursor-pointer rounded-2xl border-2 p-4 sm:p-5 transition-all flex flex-col justify-between h-full text-left overflow-hidden border-slate-200 bg-white hover:border-[#035D29] hover:shadow-md touch-manipulation group"
                  >
                    <div class="absolute top-0 left-0 w-full h-1.5" :class="v.corBarra"></div>
                    <div class="mb-3 mt-1">
                      <span :class="['text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md transition-colors', v.corBadge]">
                        {{ v.eixos }}
                      </span>
                    </div>
                    <div>
                      <h4 class="font-black text-slate-900 text-sm sm:text-[15px] leading-tight group-hover:text-[#035D29] transition-colors">{{ v.nome }}</h4>
                      <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1 font-medium leading-snug">{{ v.desc }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 1.2 TIPO DE CARROCERIA -->
              <div v-if="form.tipo_veiculo && !form.tipo_carroceria" class="animate-fade-in mt-6 sm:mt-0">
                <h3 class="text-[11px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Qual a Carroceria Necessária? <span class="text-red-500">*</span></h3>
                <div class="grid grid-cols-1 min-[400px]:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                  <div 
                    v-for="c in opcoesCarrocerias" :key="c.id"
                    @click="form.tipo_carroceria = c.id"
                    class="relative cursor-pointer rounded-2xl border-2 p-4 sm:p-5 transition-all flex flex-col text-left overflow-hidden h-full border-slate-200 bg-white hover:border-[#035D29] hover:shadow-md touch-manipulation group"
                  >
                    <div class="absolute top-0 left-0 w-full h-1.5" :class="c.corBarra"></div>
                    <div class="mb-2 mt-1">
                      <span :class="['text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md transition-colors', c.corBadge]">
                        {{ c.tag }}
                      </span>
                    </div>
                    <div>
                      <h4 class="font-black text-slate-900 text-sm sm:text-[15px] mt-1 group-hover:text-[#035D29] transition-colors">{{ c.nome }}</h4>
                      <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1 font-medium leading-relaxed">{{ c.desc }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 1.3 ESPECIFICAÇÕES DA MERCADORIA -->
              <div v-if="form.tipo_veiculo && form.tipo_carroceria" class="bg-slate-50 p-5 sm:p-8 rounded-2xl border border-slate-200 shadow-inner animate-fade-in mt-6 sm:mt-0">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 sm:mb-6">Detalhes da Mercadoria</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                  <div class="md:col-span-2">
                    <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Descrição do Produto <span class="text-red-500">*</span></label>
                    <input v-model="form.produto" type="text" placeholder="Ex: Bobinas de Aço" class="w-full px-4 py-3 sm:py-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white shadow-sm transition-colors appearance-none">
                  </div>
                  <div>
                    <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Acondicionamento <span class="text-red-500">*</span></label>
                    <select v-model="form.especie" class="w-full px-4 py-3 sm:py-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white shadow-sm transition-colors appearance-none">
                      <option value="" disabled>Selecione...</option>
                      <option value="caixas">Caixas</option>
                      <option value="paletes">Paletes</option>
                      <option value="sacaria">Sacaria</option>
                      <option value="granel">Granel</option>
                      <option value="tambores">Tambores</option>
                      <option value="outro">Outro</option>
                    </select>
                  </div>
                  <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    <div>
                      <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Peso (KG) <span class="text-red-500">*</span></label>
                      <input v-model="formVisual.peso_kg" v-maska data-maska="9.99#,##" data-maska-tokens="9:[0-9]:repeated" data-maska-reversed="true" @maska="formUnmasked.peso_kg = $event.detail.unmasked" type="text" placeholder="Ex: 32000" inputmode="numeric" class="w-full px-3 sm:px-4 py-3 sm:py-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white shadow-sm transition-colors font-mono appearance-none">
                    </div>
                    <div>
                      <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Cubagem (m³)</label>
                      <input v-model="formVisual.cubagem_m3" v-maska data-maska="9.99#,##" data-maska-tokens="9:[0-9]:repeated" data-maska-reversed="true" @maska="formUnmasked.cubagem_m3 = $event.detail.unmasked" type="text" placeholder="Ex: 45,5" inputmode="numeric" class="w-full px-3 sm:px-4 py-3 sm:py-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white shadow-sm transition-colors font-mono appearance-none">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ================= ETAPA 2: ROTA E DATAS ================= -->
            <div v-show="currentStep === 2">
              
              <!-- RESUMO DA ETAPA 2 -->
              <div v-if="form.cidade_origem" class="flex flex-col sm:flex-row gap-3 mb-6 animate-fade-in">
                <div class="flex-1 bg-slate-50 border border-slate-200 p-3 sm:p-4 rounded-xl shadow-sm flex justify-between items-center">
                  <div>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">1. Coleta</span>
                    <p class="font-bold text-slate-800 text-sm">{{ form.cidade_origem }} - {{ form.uf_origem }}</p>
                  </div>
                  <button type="button" @click="resetOrigem" class="text-xs font-bold text-[#035D29] hover:underline p-2 -mr-2">Alterar</button>
                </div>

                <div v-if="form.cidade_destino" class="flex-1 bg-slate-50 border border-slate-200 p-3 sm:p-4 rounded-xl shadow-sm flex justify-between items-center animate-fade-in">
                  <div>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">2. Entrega</span>
                    <p class="font-bold text-slate-800 text-sm">{{ form.cidade_destino }} - {{ form.uf_destino }}</p>
                  </div>
                  <button type="button" @click="resetDestino" class="text-xs font-bold text-[#035D29] hover:underline p-2 -mr-2">Alterar</button>
                </div>
              </div>

              <!-- 2.1 ORIGEM -->
              <div v-if="!form.cidade_origem" class="animate-fade-in">
                <h3 class="text-[11px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Onde o motorista deve carregar? <span class="text-red-500">*</span></h3>
                <div class="p-5 sm:p-8 bg-slate-50/50 border border-slate-200 rounded-2xl shadow-sm">
                  <label class="block text-xs sm:text-sm font-bold text-slate-800 mb-3 flex items-center">
                     <svg class="w-4 h-4 mr-2 text-[#035D29]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                     Selecione sua Doca/Galpão <span class="text-red-500 ml-1">*</span>
                  </label>
                  <select v-model="localOperacionalSelecionado" @change="aplicarLocalOrigem" class="w-full px-4 py-3.5 sm:py-4 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm font-bold bg-white shadow-sm appearance-none cursor-pointer">
                    <option value="" disabled>{{ loadingLocais ? 'Carregando suas docas...' : 'Selecione a origem...' }}</option>
                    <option v-for="local in locaisOperacionais" :key="local.id" :value="local">{{ local.nome_identificador }} - {{ local.cidade }}/{{ local.uf }}</option>
                  </select>
                </div>
              </div>

              <!-- 2.2 DESTINO (NOVO AUTO-COMPLETE INTELIGENTE EM VUE) -->
              <div v-if="form.cidade_origem && !form.cidade_destino" class="animate-fade-in mt-6 sm:mt-0">
                <h3 class="text-[11px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Para onde a carga vai? <span class="text-red-500">*</span></h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6 p-5 sm:p-8 bg-slate-50/50 border border-slate-200 rounded-2xl shadow-sm">
                  <div>
                    <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">UF Destino <span class="text-red-500">*</span></label>
                    <select v-model="form.uf_destino" @change="carregarCidadesDestino" class="w-full px-4 py-3.5 sm:py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white transition-colors shadow-sm appearance-none cursor-pointer">
                      <option value="" disabled>Estado</option>
                      <option v-for="uf in ufs" :key="uf.sigla || uf" :value="uf.sigla || uf">{{ uf.sigla || uf }}</option>
                    </select>
                  </div>
                  
                  <div class="md:col-span-2 relative">
                    <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Cidade Destino <span class="text-red-500">*</span></label>
                    
                    <div class="relative">
                      <!-- Input Visual de Pesquisa -->
                      <input 
                        v-model="buscaCidade" 
                        @focus="mostrarCidades = true"
                        @blur="fecharDropdownCidades"
                        @input="resetarCidadeEscolhida"
                        :disabled="!form.uf_destino || loadingCidadesDestino"
                        :placeholder="loadingCidadesDestino ? 'Buscando base IBGE...' : 'Digite para filtrar a cidade (Ex: Jun)'"
                        class="w-full px-4 py-3.5 sm:py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white transition-colors disabled:bg-slate-100 disabled:text-slate-400 shadow-sm"
                        autocomplete="off"
                      >
                      <!-- Lista Flutuante de Cidades -->
                      <ul v-if="mostrarCidades && !loadingCidadesDestino && form.uf_destino" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl max-h-56 overflow-y-auto ring-1 ring-black ring-opacity-5">
                        <li
                          v-for="cidade in cidadesFiltradas"
                          :key="cidade.id || cidade.nome || cidade.cidade"
                          @mousedown.prevent="selecionarCidade(cidade.nome || cidade.cidade)"
                          class="px-4 py-3 hover:bg-emerald-50 hover:text-[#035D29] cursor-pointer text-sm font-bold text-slate-700 transition-colors border-b border-slate-50 last:border-0"
                        >
                          {{ cidade.nome || cidade.cidade }}
                        </li>
                        <li v-if="cidadesFiltradas.length === 0" class="px-4 py-4 text-sm text-slate-500 font-medium text-center bg-slate-50">Nenhuma cidade localizada.</li>
                      </ul>
                    </div>

                  </div>
                </div>
              </div>
              
              <!-- 2.3 DATAS E DISTÂNCIA -->
              <div v-if="form.cidade_origem && form.cidade_destino" class="bg-slate-50 p-5 sm:p-8 rounded-2xl border border-slate-200 shadow-inner animate-fade-in mt-6 sm:mt-0">
                <h3 class="text-[11px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-4 sm:mb-6">Programação Logística</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
                  <div>
                     <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Data de Coleta <span class="text-red-500">*</span></label>
                     <input v-model="form.data_coleta" type="date" class="w-full px-4 py-3 sm:py-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white shadow-sm transition-colors appearance-none">
                  </div>
                  <div>
                     <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Entrega Limite <span class="text-red-500">*</span></label>
                     <input v-model="form.data_entrega_prevista" type="date" class="w-full px-4 py-3 sm:py-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white shadow-sm transition-colors appearance-none">
                  </div>
                  <div>
                     <label class="block text-[11px] sm:text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Distância (KM)</label>
                     <div class="flex relative shadow-sm rounded-xl">
                        <input v-model.number="form.distancia_km" type="number" step="0.1" inputmode="numeric" class="w-full pl-4 pr-24 py-3 sm:py-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-base sm:text-sm bg-white transition-colors font-mono appearance-none" placeholder="KM">
                        <button type="button" @click="calcularDistanciaMaps" :disabled="isCalculandoRota" class="absolute right-1.5 top-1.5 bottom-1.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white px-3 sm:px-4 rounded-lg text-[10px] sm:text-xs font-bold transition-colors flex items-center shadow-sm touch-manipulation">
                          {{ isCalculandoRota ? 'Calculando' : 'Calcular' }}
                        </button>
                     </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ================= ETAPA 3: VALOR, PEDÁGIO E REVISÃO ================= -->
            <div v-show="currentStep === 3" class="space-y-8 animate-fade-in">
              <div class="bg-gradient-to-br from-slate-50 to-emerald-50/30 border border-[#035D29]/20 p-6 sm:p-12 rounded-3xl text-center shadow-sm">
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-2 sm:mb-3">A Oferta Financeira</h3>
                <p class="text-sm sm:text-base text-slate-600 mb-8 sm:mb-10 font-medium max-w-lg mx-auto">Defina os valores que serão repassados ao transportador via CIOT para fechamento imediato.</p>
                
                <div class="max-w-xl mx-auto">
                   <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 mb-6">
                      
                      <!-- CAMPO 1: FRETE LÍQUIDO -->
                      <div class="relative">
                         <label class="block text-left text-[11px] sm:text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Frete Líquido <span class="text-red-500">*</span></label>
                         <span class="absolute left-4 top-[36px] sm:top-[38px] text-lg sm:text-xl font-black text-slate-400">R$</span>
                         <input 
                           v-model="formVisual.valor_frete" 
                           v-maska data-maska="9.99#,##" data-maska-tokens="9:[0-9]:repeated" data-maska-reversed="true"
                           @maska="formUnmasked.valor_frete = $event.detail.unmasked"
                           @blur="validarPisoMinimo"
                           type="text" inputmode="numeric" placeholder="0,00" 
                           :class="[
                             'w-full pl-12 sm:pl-14 pr-4 py-4 sm:py-5 border-2 rounded-2xl text-xl sm:text-2xl font-black transition-all shadow-inner focus:outline-none appearance-none',
                             erroAntt ? 'border-red-400 bg-red-50 text-red-900 focus:border-red-500' : 'border-[#035D29] bg-white text-[#035D29] focus:ring-4 focus:ring-[#035D29]/20'
                           ]"
                         >
                      </div>

                      <!-- CAMPO 2: VALE PEDÁGIO -->
                      <div class="relative">
                         <label class="block text-left text-[11px] sm:text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Vale-Pedágio (Opcional)</label>
                         <span class="absolute left-4 top-[36px] sm:top-[38px] text-lg sm:text-xl font-black text-slate-400">R$</span>
                         <input 
                           v-model="formVisual.pedagio" 
                           v-maska data-maska="9.99#,##" data-maska-tokens="9:[0-9]:repeated" data-maska-reversed="true"
                           @maska="formUnmasked.pedagio = $event.detail.unmasked"
                           type="text" inputmode="numeric" placeholder="0,00" 
                           class="w-full pl-12 sm:pl-14 pr-4 py-4 sm:py-5 border-2 border-slate-300 rounded-2xl text-xl sm:text-2xl font-black transition-all shadow-inner focus:outline-none focus:border-[#035D29] focus:ring-4 focus:ring-[#035D29]/20 appearance-none bg-white text-slate-700"
                         >
                      </div>
                   </div>

                   <!-- TOTALIZADOR -->
                   <div class="mt-4 p-5 bg-gradient-to-r from-[#035D29] to-[#047c36] rounded-2xl text-white flex justify-between items-center shadow-lg shadow-[#035D29]/20">
                      <span class="text-xs font-black uppercase tracking-widest text-emerald-100/90">Total ao Motorista:</span>
                      <span class="text-2xl sm:text-3xl font-black">R$ {{ formatarMoeda(calcularTotalOferta()) }}</span>
                   </div>
                   
                   <!-- Feedback da ANTT -->
                   <div v-if="isCalculandoAntt" class="mt-4 sm:mt-6 text-xs sm:text-sm text-[#ff5500] flex items-center justify-center font-bold">
                      <svg class="animate-spin -ml-1 mr-2 h-4 w-4 sm:h-5 sm:w-5 text-[#ff5500]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                      A consultar tabela oficial da ANTT...
                   </div>
                   <div v-else-if="valorMinimoAntt" class="mt-4 sm:mt-6 flex flex-col font-bold text-xs sm:text-sm bg-white p-3 sm:p-4 rounded-xl border border-slate-200 shadow-sm">
                      <span :class="erroAntt ? 'text-red-600' : 'text-[#035D29]'">
                        ✔ Piso Mínimo Permitido (Sem Pedágio): R$ {{ formatarMoeda(valorMinimoAntt) }}
                      </span>
                      <span v-if="erroAntt" class="text-red-500 mt-2 font-medium bg-red-50 p-2 sm:p-3 rounded-lg">{{ erroAntt }}</span>
                   </div>
                </div>
              </div>
            </div>

            <!-- NAVEGAÇÃO DE ETAPAS (FOOTER ULTRA RESPONSIVO) -->
            <div class="pt-6 sm:pt-8 mt-8 sm:mt-10 border-t border-slate-200 flex flex-col sm:flex-row sm:justify-between gap-3 sm:gap-0 pb-8 sm:pb-0">
              <button 
                type="button" 
                v-if="currentStep > 1" 
                @click="currentStep--" 
                class="w-full sm:w-auto px-8 py-4 sm:py-3.5 border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 focus:outline-none transition-colors shadow-sm order-2 sm:order-1"
              >
                Voltar
              </button>
              <div v-else class="hidden sm:block order-2 sm:order-1"></div>

              <button 
                type="button" 
                v-if="currentStep < 3" 
                @click="nextStep" 
                :disabled="isNextStepDisabled()"
                class="w-full sm:w-auto px-10 py-4 sm:py-3.5 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 focus:outline-none transition-colors shadow-md flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed order-1 sm:order-2 touch-manipulation"
              >
                Próxima Etapa
              </button>
              
              <button 
                type="button"
                v-if="currentStep === 3"
                @click="finalizarPublicacao"
                :disabled="isSubmitting || auth.user?.status === 'pending'"
                class="w-full sm:w-auto px-8 sm:px-10 py-4 sm:py-3.5 bg-[#035D29] text-white font-black rounded-xl hover:bg-[#023818] focus:outline-none focus:ring-4 focus:ring-[#035D29]/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg flex items-center justify-center uppercase tracking-wider order-1 sm:order-2 touch-manipulation"
              >
                {{ isSubmitting ? 'Aguarde...' : 'Publicar Frete' }}
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';

const auth = useAuthStore();
const router = useRouter();

// ========================================================
// DADOS DOS CARDS DE SELEÇÃO VISUAL
// ========================================================
const opcoesVeiculos = [
  { id: 'fiorino', nome: 'Fiorino / Van', eixos: '2 Eixos (Leve)', desc: 'Até 1.500 kg', corBarra: 'bg-blue-400', corBadge: 'bg-blue-50 text-blue-700' },
  { id: 'toco', nome: 'Caminhão Toco', eixos: '2 Eixos', desc: 'Até 6.000 kg', corBarra: 'bg-blue-400', corBadge: 'bg-blue-50 text-blue-700' },
  { id: 'truck', nome: 'Caminhão Truck', eixos: '3 Eixos (Médio)', desc: 'Até 14.000 kg', corBarra: 'bg-amber-400', corBadge: 'bg-amber-50 text-amber-700' },
  { id: 'bitruck', nome: 'Bitruck', eixos: '4 Eixos', desc: 'Até 22.000 kg', corBarra: 'bg-amber-400', corBadge: 'bg-amber-50 text-amber-700' },
  { id: 'carreta', nome: 'Carreta Simples', eixos: '5 Eixos (Pesado)', desc: 'Até 25.000 kg', corBarra: 'bg-purple-500', corBadge: 'bg-purple-50 text-purple-700' },
  { id: 'carreta_ls', nome: 'Carreta LS', eixos: '6 Eixos', desc: 'Até 32.000 kg', corBarra: 'bg-purple-500', corBadge: 'bg-purple-50 text-purple-700' },
  { id: 'vanderleia', nome: 'Vanderléia', eixos: '6 Eixos Especiais', desc: 'Eixos distanciados', corBarra: 'bg-slate-700', corBadge: 'bg-slate-100 text-slate-700' },
  { id: 'bitrem', nome: 'Bitrem / Rodo', eixos: '7+ Eixos (Extra)', desc: 'Acima de 50 Ton.', corBarra: 'bg-slate-700', corBadge: 'bg-slate-100 text-slate-700' }
];

const opcoesCarrocerias = [
  { id: 'bau', nome: 'Baú Fechado', desc: 'Proteção climática.', tag: 'Fechada', corBarra: 'bg-indigo-500', corBadge: 'bg-indigo-50 text-indigo-700' },
  { id: 'sider', nome: 'Sider (Cortina)', desc: 'Acesso lateral ideal para paletes.', tag: 'Fechada', corBarra: 'bg-indigo-500', corBadge: 'bg-indigo-50 text-indigo-700' },
  { id: 'aberta', nome: 'Carga Seca', desc: 'Acesso livre e sem teto.', tag: 'Aberta', corBarra: 'bg-orange-400', corBadge: 'bg-orange-50 text-orange-700' },
  { id: 'graneleiro', nome: 'Graneleiro', desc: 'Tampas altas para grãos.', tag: 'Aberta', corBarra: 'bg-orange-400', corBadge: 'bg-orange-50 text-orange-700' },
  { id: 'frigorifico', nome: 'Frigorífico', desc: 'Controle de temperatura.', tag: 'Refrigerada', corBarra: 'bg-cyan-400', corBadge: 'bg-cyan-50 text-cyan-700' },
  { id: 'prancha', nome: 'Prancha', desc: 'Cargas indivisíveis.', tag: 'Especial', corBarra: 'bg-slate-700', corBadge: 'bg-slate-100 text-slate-700' }
];

const getVeiculoNome = (id) => opcoesVeiculos.find(v => v.id === id)?.nome;
const getCarroceriaNome = (id) => opcoesCarrocerias.find(c => c.id === id)?.nome;

// ========================================================
// CONTROLE DE ESTADO DA TELA
// ========================================================
const currentStep = ref(1);

const form = ref({
  produto: '',
  especie: '',
  tipo_veiculo: '',
  tipo_carroceria: '',
  uf_origem: '',
  cidade_origem: '',
  uf_destino: '',
  cidade_destino: '',
  data_coleta: '',
  data_entrega_prevista: '',
  distancia_km: null,
});

const locaisOperacionais = ref([]);
const loadingLocais = ref(true);
const localOperacionalSelecionado = ref('');

const formVisual = ref({ peso_kg: '', cubagem_m3: '', valor_frete: '', pedagio: '' });
const formUnmasked = ref({ peso_kg: '', cubagem_m3: '', valor_frete: '', pedagio: '' });
const isSubmitting = ref(false);

const ufs = ref([]);
const cidadesDestino = ref([]);
const loadingCidadesDestino = ref(false);

// CONTROLE DO AUTO-COMPLETE INTELIGENTE
const buscaCidade = ref('');
const mostrarCidades = ref(false);

const isCalculandoRota = ref(false);
const isCalculandoAntt = ref(false);
const valorMinimoAntt = ref(null);
const erroAntt = ref('');

// ========================================================
// INICIALIZAÇÃO E CARREGAMENTO DE DADOS
// ========================================================
onMounted(async () => {
  try {
    const resUfs = await axios.get('/api/v1/localidades/estados');
    ufs.value = resUfs.data;

    const resLocais = await axios.get('/api/v1/embarcador/locais');
    locaisOperacionais.value = resLocais.data;
    
    const localPadrao = locaisOperacionais.value.find(l => l.is_padrao);
    if (localPadrao) {
        localOperacionalSelecionado.value = localPadrao;
        aplicarLocalOrigem();
    } else if (locaisOperacionais.value.length > 0) {
        localOperacionalSelecionado.value = locaisOperacionais.value[0];
        aplicarLocalOrigem();
    }
  } catch (e) {
    console.error('Erro:', e);
  } finally {
      loadingLocais.value = false;
  }
});

const aplicarLocalOrigem = () => {
    if (localOperacionalSelecionado.value) {
        form.value.uf_origem = localOperacionalSelecionado.value.uf;
        form.value.cidade_origem = localOperacionalSelecionado.value.cidade;
    }
};

const carregarCidadesDestino = async () => {
  const uf = form.value.uf_destino;
  if (!uf) return;

  // Reseta os dados de cidade sempre que trocar o estado
  form.value.cidade_destino = '';
  buscaCidade.value = '';
  
  loadingCidadesDestino.value = true;
  
  const cacheKey = `cidades_local_${uf}`;
  const cached = sessionStorage.getItem(cacheKey);

  if (cached) {
    cidadesDestino.value = JSON.parse(cached);
    loadingCidadesDestino.value = false;
    return;
  }

  try {
    const res = await axios.get(`/api/v1/localidades/estados/${uf}/municipios`);
    cidadesDestino.value = res.data;
    sessionStorage.setItem(cacheKey, JSON.stringify(res.data));
  } catch (error) {
  } finally {
    loadingCidadesDestino.value = false;
  }
};

// ========================================================
// LÓGICA DO AUTO-COMPLETE INTELIGENTE
// ========================================================

// Cria uma lista virtual em tempo real baseada no que o usuário digita
const cidadesFiltradas = computed(() => {
  if (!buscaCidade.value) return cidadesDestino.value;
  
  const termo = buscaCidade.value.toLowerCase();
  return cidadesDestino.value.filter(c => {
    const nome = c.nome || c.cidade;
    return nome.toLowerCase().includes(termo);
  });
});

// Impede travamentos da UI ao perder o foco (Espera 200ms pro clique no item funcionar)
const fecharDropdownCidades = () => {
  setTimeout(() => { mostrarCidades.value = false; }, 200);
};

// Limpa o valor "Oficial" de destino se o usuário mexer no teclado de novo
const resetarCidadeEscolhida = () => {
  form.value.cidade_destino = '';
  mostrarCidades.value = true;
};

// Grava o valor real apenas quando ele clicar na opção
const selecionarCidade = (nome) => {
  form.value.cidade_destino = nome; // Valor oficial que vai pro banco
  buscaCidade.value = nome;         // Valor visual no input
  mostrarCidades.value = false;     // Esconde a lista
};


// ========================================================
// REINICIAR ETAPAS (RESET)
// ========================================================
const resetVeiculo = () => {
  form.value.tipo_veiculo = '';
  form.value.tipo_carroceria = '';
};

const resetOrigem = () => {
  localOperacionalSelecionado.value = '';
  form.value.uf_origem = '';
  form.value.cidade_origem = '';
  form.value.uf_destino = '';
  form.value.cidade_destino = '';
  buscaCidade.value = '';
};

const resetDestino = () => {
  form.value.uf_destino = '';
  form.value.cidade_destino = '';
  buscaCidade.value = '';
};

// ========================================================
// CONTROLE DE ETAPAS E TOTALIZADOR
// ========================================================
const isNextStepDisabled = () => {
  if (currentStep.value === 1) {
    return !form.value.tipo_veiculo || !form.value.tipo_carroceria || !form.value.produto || !form.value.especie || !formUnmasked.value.peso_kg;
  }
  if (currentStep.value === 2) {
    // AQUI ESTÁ A PROTEÇÃO: Se não tiver o dado OFICIAL da cidade, o botão trava!
    return !form.value.cidade_origem || !form.value.cidade_destino || !form.value.data_coleta || !form.value.data_entrega_prevista || !form.value.distancia_km;
  }
  return false;
};

const nextStep = () => {
  currentStep.value++;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Soma Dinâmica na Tela (Frete + Pedágio)
const calcularTotalOferta = () => {
  const frete = formatStringToFloat(formUnmasked.value.valor_frete) || 0;
  const ped = formatStringToFloat(formUnmasked.value.pedagio) || 0;
  return frete + ped;
};

// ========================================================
// INTELIGÊNCIA ANTT E CÁLCULO REAL (MOTOR INDEPENDENTE)
// ========================================================
const calcularDistanciaMaps = async () => {
  if (!form.value.cidade_origem || !form.value.cidade_destino || !form.value.uf_origem || !form.value.uf_destino) {
    alert('Preencha a origem e o destino completos primeiro!');
    return;
  }
  
  isCalculandoRota.value = true;
  
  try {
    const payload = {
        cidade_origem: form.value.cidade_origem,
        uf_origem: form.value.uf_origem,
        cidade_destino: form.value.cidade_destino,
        uf_destino: form.value.uf_destino
    };
    
    const res = await axios.post('/api/v1/antt/distancia', payload);
    form.value.distancia_km = res.data.distancia_km;
    
  } catch (error) {
    console.error("Erro Roteirização:", error);
    alert(error.response?.data?.error || 'Falha ao calcular rota oficial.');
  } finally {
    isCalculandoRota.value = false;
  }
};

const mapVeiculoParaEixos = (v) => ({ 'fiorino': 2, 'toco': 2, 'truck': 3, 'bitruck': 4, 'carreta': 5, 'carreta_ls': 6, 'vanderleia': 6, 'bitrem': 7 }[v] || 2);
const mapCarroceriaParaTipoCarga = (c) => c === 'frigorifico' ? 'Frigorificada' : 'Geral';

watch(
  () => [form.value.distancia_km, form.value.tipo_veiculo, form.value.tipo_carroceria],
  async ([distancia, veiculo, carroceria]) => {
    if (distancia > 0 && veiculo && carroceria) {
      await buscarPisoMinimoAntt(distancia, veiculo, carroceria);
    }
  }
);

const buscarPisoMinimoAntt = async (distancia, veiculo, carroceria) => {
  isCalculandoAntt.value = true;
  try {
    const payload = {
      distancia_km: distancia,
      eixos: mapVeiculoParaEixos(veiculo),
      tipo_carga: mapCarroceriaParaTipoCarga(carroceria)
    };
    const res = await axios.post('/api/v1/antt/calcular', payload);
    valorMinimoAntt.value = res.data.valor_minimo_antt;
    validarPisoMinimo();
  } catch (e) {
    valorMinimoAntt.value = null;
  } finally {
    isCalculandoAntt.value = false;
  }
};

const validarPisoMinimo = () => {
  erroAntt.value = '';
  if (!valorMinimoAntt.value) return;

  const valorDigitado = formatStringToFloat(formUnmasked.value.valor_frete) || 0;

  if (valorDigitado > 0 && valorDigitado < valorMinimoAntt.value) {
    erroAntt.value = `A lei exige o pagamento mínimo de R$ ${formatarMoeda(valorMinimoAntt.value)} (Descontando Pedágio)`;
    formVisual.value.valor_frete = formatarMoeda(valorMinimoAntt.value);
    formUnmasked.value.valor_frete = (valorMinimoAntt.value * 100).toFixed(0); 
  }
};

const formatStringToFloat = (val) => val ? parseFloat(val) / 100 : null;
const formatarMoeda = (valor) => valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const finalizarPublicacao = async () => {
  if (auth.user?.status === 'pending') return;
  
  if (!formUnmasked.value.valor_frete) {
    alert("⚠️ Defina o valor da oferta de frete.");
    return;
  }

  validarPisoMinimo();
  if (erroAntt.value !== '') {
    alert('Erro de Compliance: ' + erroAntt.value);
    return;
  }

  isSubmitting.value = true;
  
  // Inclui o pedágio e o cálculo base da ANTT no Payload enviado para o Backend
  const payload = {
    ...form.value,
    peso_kg: formatStringToFloat(formUnmasked.value.peso_kg),
    cubagem_m3: formatStringToFloat(formUnmasked.value.cubagem_m3),
    valor_frete: formatStringToFloat(formUnmasked.value.valor_frete),
    pedagio: formatStringToFloat(formUnmasked.value.pedagio) || 0,
    piso_antt: valorMinimoAntt.value // GUARDA O PISO EXATO DO MOMENTO NO BANCO
  };

  try {
    await axios.post('/api/v1/embarcador/cargas', payload);
    alert('✅ Frete publicado com sucesso no Mural de Operações!');
    router.push({ name: 'EmbarcadorDashboard' });
  } catch (error) {
    if (error.response?.status === 422) {
      alert('Inconsistência: \n\n' + Object.values(error.response.data.errors).flat().join('\n'));
    } else {
      alert('Falha de comunicação com o cluster logístico.');
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

/* Remove setas de inputs numéricos no Safari/Chrome */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] {
  -moz-appearance: textfield;
}

/* Reset nativo do Safari para inputs e selects */
select {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}
</style>