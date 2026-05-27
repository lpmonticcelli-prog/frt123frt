<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <!-- HEADER -->
    <div class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-surface-200 bg-surface-900 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Ad Server & Rede de Parceiros</h2>
          <p class="text-sm text-surface-400">Motor de Tráfego: Gestão de Campanhas, Banners e Contratos.</p>
        </div>
        <button @click="abrirModalCriacao" :disabled="isSaving || loading" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-md shadow-clinical-sm transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 flex items-center gap-2 disabled:opacity-50">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          Nova Campanha
        </button>
      </div>
    </div>

    <!-- LOADER -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
    </div>

    <!-- TABELA DE RESULTADOS -->
    <div v-else class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden animate-fade-in">
      <div class="overflow-x-auto scrollbar-clinical">
        <table class="min-w-full divide-y divide-surface-200">
          <thead class="bg-surface-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Campanha</th>
              <th class="px-6 py-3 text-center text-xs font-bold text-surface-500 uppercase tracking-wider">Estratégia (Modelo)</th>
              <th class="px-6 py-3 text-center text-xs font-bold text-surface-500 uppercase tracking-wider">Métricas Reais</th>
              <th class="px-6 py-3 text-center text-xs font-bold text-surface-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-right text-xs font-bold text-surface-500 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-200">
            <tr v-if="parceiros.length === 0">
              <td colspan="5" class="px-6 py-10 text-center text-surface-500 font-bold uppercase tracking-widest text-xs">Nenhuma campanha rodando.</td>
            </tr>
            <tr v-for="item in parceiros" :key="item.id" class="hover:bg-surface-50 transition-colors" :class="{'opacity-50': !item.is_active}">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div v-if="item.imagem_url" class="flex-shrink-0 h-12 w-16 mr-4 rounded border border-surface-200 overflow-hidden bg-surface-100">
                    <img class="h-full w-full object-cover" :src="item.imagem_url" alt="" loading="lazy" />
                  </div>
                  <div v-else class="flex-shrink-0 h-12 w-16 mr-4 bg-surface-100 rounded flex items-center justify-center text-surface-400 border border-surface-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  </div>
                  <div>
                    <div class="text-sm font-black text-surface-900 truncate max-w-[200px]" :title="item.nome">{{ item.nome }}</div>
                    <div class="text-[10px] text-surface-500 font-bold uppercase tracking-widest mt-0.5">Público: {{ item.audience }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                 <span class="px-2 py-1 text-[10px] font-black rounded uppercase bg-indigo-50 text-indigo-700 border border-indigo-200 tracking-widest">
                   {{ item.modelo_cobranca }}
                 </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-xs tabular-nums">
                  <div v-if="item.modelo_cobranca === 'cpc'" class="font-bold text-surface-600">
                    {{ item.cliques_acumulados || 0 }} / {{ item.limite_cliques }} Clicks
                  </div>
                  <div v-else-if="item.modelo_cobranca === 'cpa'" class="font-bold text-surface-600">
                    {{ item.conversoes_acumuladas || 0 }} / {{ item.limite_conversoes }} Leads
                  </div>
                  <div v-else-if="item.modelo_cobranca === 'assinatura'" class="font-bold text-surface-600">
                    Vence: {{ item.data_expiracao ? new Date(item.data_expiracao).toLocaleDateString() : 'N/A' }}
                  </div>
                  <div v-else class="font-black text-surface-400 uppercase tracking-widest text-[10px]">- Infinito -</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                 <span class="px-2 py-1 text-[10px] tracking-widest font-black rounded uppercase" 
                       :class="item.is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200'">
                  {{ item.is_active ? 'Rodando' : 'Pausado' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                 <button @click="abrirModalEdicao(item)" :disabled="isSaving" class="px-3 py-1.5 bg-surface-200 text-surface-800 hover:bg-surface-300 text-xs font-bold uppercase tracking-widest rounded shadow-clinical-sm transition-colors focus:outline-none focus:ring-2 focus:ring-surface-500 disabled:opacity-50">Editar</button>
                 <button @click="excluirParceiro(item.id)" :disabled="isSaving" class="px-3 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 text-xs font-bold uppercase tracking-widest rounded shadow-clinical-sm transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:opacity-50">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- OFFCANVAS DRAWER (MODAL LATERAL) COM PREVIEW -->
    <transition
      enter-active-class="transition ease-in-out duration-300 transform"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition ease-in-out duration-300 transform"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <div v-if="showModal" class="fixed inset-y-0 right-0 z-modal w-full max-w-5xl bg-white shadow-2xl flex flex-col border-l border-surface-300 overflow-hidden">
        
        <!-- HEADER DO DRAWER -->
        <div class="px-6 py-4 bg-surface-900 text-white flex justify-between items-center shrink-0 shadow-md z-20">
          <h3 class="text-lg font-black uppercase tracking-widest">{{ isEditing ? 'Ajustar Campanha' : 'Nova Campanha de Tráfego' }}</h3>
          <button @click="fecharModal" class="text-surface-400 hover:text-white text-2xl font-black focus:outline-none transition-colors leading-none">&times;</button>
        </div>

        <!-- SPLIT SCREEN: FORMULÁRIO vs PREVIEW -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden bg-surface-50">
          
          <!-- COLUNA ESQUERDA: ENGENHARIA DE DADOS (Formulário) -->
          <div class="w-full lg:w-3/5 overflow-y-auto scrollbar-clinical p-6 bg-white border-r border-surface-200">
            <form @submit.prevent="salvar" class="space-y-5" id="adForm">
              
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                  <label class="block text-[11px] font-black text-surface-600 uppercase tracking-widest mb-1">Título da Campanha</label>
                  <input v-model.trim="form.nome" type="text" required maxlength="255" class="w-full border-surface-300 rounded focus:ring-brand-500 focus:border-brand-500 text-sm p-2.5 border font-medium" placeholder="Ex: Mega Promoção Goodyear">
                </div>

                <div>
                  <label class="block text-[11px] font-black text-surface-600 uppercase tracking-widest mb-1">Público-Alvo</label>
                  <select v-model="form.audience" class="w-full border-surface-300 rounded focus:ring-brand-500 focus:border-brand-500 text-sm p-2.5 border bg-white font-medium">
                    <option value="todos">Todos (Global)</option>
                    <option value="motorista">Apenas Motoristas</option>
                    <option value="embarcador">Apenas Embarcadores</option>
                  </select>
                </div>

                <div>
                  <label class="block text-[11px] font-black text-surface-600 uppercase tracking-widest mb-1">Posicionamento</label>
                  <select v-model="form.posicionamento" class="w-full border-surface-300 rounded focus:ring-brand-500 focus:border-brand-500 text-sm p-2.5 border bg-white font-medium">
                    <option value="topo">Header (Topo 1200x150px)</option>
                    <option value="rodape">Footer (Rodapé 1200x150px)</option>
                    <option value="lateral">Sidebar (Lateral 600x600px)</option>
                    <option value="direita">Mural (Direita 600x1200px)</option>
                  </select>
                </div>

                <!-- URLs Blindadas com MaxLength -->
                <div class="col-span-2">
                  <label class="block text-[11px] font-black text-surface-600 uppercase tracking-widest mb-1">URL da Mídia (Banner)</label>
                  <input v-model.trim="form.imagem_url" type="url" maxlength="1000" class="w-full border-surface-300 rounded focus:ring-brand-500 focus:border-brand-500 text-sm p-2.5 border font-medium placeholder-surface-300" placeholder="https://seudominio.com/banner.jpg">
                </div>

                <div class="col-span-2">
                  <label class="block text-[11px] font-black text-surface-600 uppercase tracking-widest mb-1">URL de Destino (Ao clicar)</label>
                  <input v-model.trim="form.link_url" type="url" maxlength="1000" class="w-full border-surface-300 rounded focus:ring-brand-500 focus:border-brand-500 text-sm p-2.5 border font-medium placeholder-surface-300" placeholder="https://landingpage.com.br/promo">
                </div>
                
                <div class="col-span-2">
                  <label class="block text-[11px] font-black text-surface-600 uppercase tracking-widest mb-1">Copy Alternativo (Se não houver imagem)</label>
                  <textarea v-model.trim="form.descricao" rows="2" maxlength="500" class="w-full border-surface-300 rounded focus:ring-brand-500 focus:border-brand-500 text-sm p-2.5 border resize-none font-medium"></textarea>
                </div>

                <!-- MOTOR DA GPU -->
                <div class="col-span-2 pt-4 pb-2 border-b border-surface-200 flex items-center justify-between mt-2">
                  <h4 class="text-xs font-black text-brand-600 uppercase tracking-widest flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Comportamento Físico do Banner (GPU)
                  </h4>
                </div>

                <div class="col-span-2 grid grid-cols-2 gap-4 bg-brand-50 p-4 rounded-xl border border-brand-200">
                  <div class="col-span-2 flex items-center justify-between">
                    <div>
                      <span class="block text-[11px] font-black text-brand-900 uppercase tracking-widest">Modo de Exibição</span>
                      <span class="text-[9px] font-medium text-brand-700">O banner deve girar em loop infinito ou ficar fixo?</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="form.estatico" class="sr-only peer">
                      <div class="w-11 h-6 bg-surface-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-brand-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                      <span class="ml-3 text-[10px] font-black text-brand-800 uppercase">{{ form.estatico ? 'Fixo / Estático' : 'Carrossel / GPU' }}</span>
                    </label>
                  </div>

                  <div class="col-span-2 mt-2" :class="{'opacity-50 pointer-events-none': form.estatico}">
                    <label class="block text-[11px] font-black text-brand-900 uppercase tracking-widest mb-1 flex justify-between">
                      Velocidade da Animação (Segundos)
                      <span class="text-brand-600 tabular-nums">{{ form.velocidade }}s</span>
                    </label>
                    <input v-model.number="form.velocidade" type="range" min="0" max="100" step="5" class="w-full h-2 bg-brand-200 rounded-lg appearance-none cursor-pointer accent-brand-600">
                    <div class="flex justify-between text-[9px] font-bold text-brand-600 uppercase mt-1">
                      <span>0s (Fixo)</span>
                      <span>Lento (100s)</span>
                    </div>
                  </div>
                </div>

                <!-- REGRAS DE FATURAMENTO -->
                <div class="col-span-2 pt-4 pb-2 border-b border-surface-200 mt-2">
                  <h4 class="text-xs font-black text-surface-800 uppercase tracking-widest">Regras de Faturamento</h4>
                </div>

                <div>
                  <label class="block text-[11px] font-black text-surface-600 uppercase tracking-widest mb-1">Status da Fatura</label>
                  <select v-model="form.status_financeiro" class="w-full border-surface-300 rounded focus:ring-brand-500 focus:border-brand-500 text-sm p-2.5 border bg-white font-medium">
                    <option value="pendente">Bloqueado (Pendente)</option>
                    <option value="pago">Liberado (Pago)</option>
                    <option value="isento">Isento / Bônus</option>
                  </select>
                </div>

                <div>
                  <label class="block text-[11px] font-black text-surface-600 uppercase tracking-widest mb-1">Estratégia de Consumo</label>
                  <select v-model="form.modelo_cobranca" class="w-full border-indigo-300 rounded focus:ring-indigo-500 focus:border-indigo-500 text-sm p-2.5 border bg-indigo-50 text-indigo-900 font-bold">
                    <option value="assinatura">Tempo de Tela (Assinatura)</option>
                    <option value="cpc">CPC (Pacote de Cliques)</option>
                    <option value="cpa">CPA (Pacote de Conversões)</option>
                    <option value="gratuito">Infinito (Gratuito)</option>
                  </select>
                </div>

                <div v-if="form.modelo_cobranca === 'assinatura'" class="col-span-2 bg-blue-50 p-4 border border-blue-200 rounded-lg animate-fade-in">
                  <label class="block text-[11px] font-black text-blue-800 uppercase tracking-widest mb-1">Dias Contratados</label>
                  <input v-model.number="form.dias_duracao" type="number" min="1" class="w-full border-blue-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2.5 border bg-white tabular-nums">
                </div>

                <div v-if="form.modelo_cobranca === 'cpc'" class="col-span-2 bg-purple-50 p-4 border border-purple-200 rounded-lg animate-fade-in">
                  <label class="block text-[11px] font-black text-purple-800 uppercase tracking-widest mb-1">Teto Máximo de Cliques</label>
                  <input v-model.number="form.limite_cliques" type="number" min="1" class="w-full border-purple-300 rounded focus:ring-purple-500 focus:border-purple-500 text-sm p-2.5 border bg-white tabular-nums">
                </div>

                <div v-if="form.modelo_cobranca === 'cpa'" class="col-span-2 bg-emerald-50 p-4 border border-emerald-200 rounded-lg animate-fade-in">
                  <label class="block text-[11px] font-black text-emerald-800 uppercase tracking-widest mb-1">Teto Máximo de Leads</label>
                  <input v-model.number="form.limite_conversoes" type="number" min="1" class="w-full border-emerald-300 rounded focus:ring-emerald-500 focus:border-emerald-500 text-sm p-2.5 border bg-white tabular-nums">
                </div>

                <div class="col-span-2 flex items-center mt-4 p-4 bg-surface-50 border border-surface-200 rounded-lg">
                  <input v-model="form.is_active" id="is_active" type="checkbox" class="h-5 w-5 text-brand-600 border-surface-300 rounded focus:ring-brand-500">
                  <label for="is_active" class="ml-3 block text-sm text-surface-900 font-bold uppercase tracking-widest">Ativar Anúncio (Override Master)</label>
                </div>

              </div>
            </form>
          </div>

          <!-- COLUNA DIREITA: LIVE PREVIEW (Simulação Visual) -->
          <div class="w-full lg:w-2/5 bg-surface-800 relative flex flex-col p-6 shadow-inner">
            <div class="flex items-center justify-between mb-6 shrink-0">
              <h4 class="text-xs font-black text-brand-400 uppercase tracking-widest flex items-center">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse mr-2"></span> Live Preview 
              </h4>
              <span class="px-2 py-1 bg-surface-700 text-surface-300 text-[9px] font-black rounded uppercase tracking-widest">Zona: {{ form.posicionamento }}</span>
            </div>

            <div class="flex-1 flex items-center justify-center border-2 border-dashed border-surface-600 rounded-xl p-4 overflow-hidden relative">
              
              <!-- Container Dimensional Simulado -->
              <div 
                class="bg-surface-50 border border-surface-200 rounded-md shadow-clinical-md overflow-hidden relative flex justify-center group pointer-events-none"
                :class="previewDimensionsClass"
              >
                <!-- Renderizador Híbrido Cópia do AdCarousel -->
                <div 
                  :class="(form.estatico || form.velocidade <= 0) ? 'flex flex-col w-full h-full overflow-y-auto scrollbar-clinical' : 'flex flex-col w-full absolute top-0 animate-scroll-vertical-up'"
                  :style="(!form.estatico && form.velocidade > 0) ? { animationDuration: `${form.velocidade}s` } : {}"
                >
                  <!-- Bloco Principal -->
                  <div class="flex flex-col gap-3 p-2">
                    <div v-for="n in ((form.estatico || form.velocidade <= 0) ? 1 : 2)" :key="`prev1-${n}`" class="block w-full overflow-hidden rounded shadow-clinical-sm border border-surface-200 bg-white shrink-0">
                      <!-- Injeção Sanitizada da URL -->
                      <img v-if="previewImageUrl" :src="previewImageUrl" class="w-full h-auto object-cover" />
                      <div v-else class="p-3 text-center">
                        <p class="text-xs font-black text-surface-800 truncate">{{ form.nome || 'Parceiro' }}</p>
                        <p class="text-[9px] text-brand-600 font-bold uppercase mt-1 truncate">{{ form.descricao || 'Saiba Mais' }}</p>
                      </div>
                    </div>
                  </div>
                  <!-- Bloco 2 (Loop Infinito) -->
                  <div v-if="!form.estatico && form.velocidade > 0" class="flex flex-col gap-3 p-2">
                    <div v-for="n in 2" :key="`prev2-${n}`" class="block w-full overflow-hidden rounded shadow-clinical-sm border border-surface-200 bg-white shrink-0">
                      <img v-if="previewImageUrl" :src="previewImageUrl" class="w-full h-auto object-cover" />
                      <div v-else class="p-3 text-center">
                        <p class="text-xs font-black text-surface-800 truncate">{{ form.nome || 'Parceiro' }}</p>
                        <p class="text-[9px] text-brand-600 font-bold uppercase mt-1 truncate">{{ form.descricao || 'Saiba Mais' }}</p>
                      </div>
                    </div>
                  </div>
                </div>

              </div>

            </div>
            
            <div class="mt-4 text-center">
               <p class="text-[10px] text-surface-500 font-bold uppercase tracking-widest">Resolução Recomendada para esta zona: <span class="text-white">{{ dimensaoRecomendada }}</span></p>
            </div>
          </div>

        </div>

        <!-- FOOTER DO DRAWER -->
        <div class="bg-white px-6 py-4 flex justify-end items-center border-t border-surface-200 gap-3 shrink-0 z-20">
          <button type="button" @click="fecharModal" :disabled="isSaving" class="text-xs font-bold uppercase tracking-widest text-surface-600 hover:text-surface-900 px-6 py-2.5 border border-surface-300 bg-white rounded-md shadow-clinical-sm focus:outline-none transition-colors disabled:opacity-50">Cancelar</button>
          <button type="submit" form="adForm" :disabled="isSaving" class="bg-brand-600 hover:bg-brand-700 text-white font-black uppercase tracking-widest py-2.5 px-8 rounded-md shadow-clinical-sm transition-colors disabled:opacity-50 flex items-center">
            <svg v-if="isSaving" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            {{ isSaving ? 'Salvando...' : 'Confirmar e Publicar' }}
          </button>
        </div>

      </div>
    </transition>
    
    <!-- BACKDROP DO OFFCANVAS -->
    <transition enter-active-class="transition-opacity ease-linear duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity ease-linear duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showModal" class="fixed inset-0 bg-surface-950/60 z-modal-backdrop backdrop-blur-sm" @click="fecharModal"></div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const parceiros = ref([]);
const loading = ref(true);
const isSaving = ref(false);
const showModal = ref(false);
const isEditing = ref(false);

const baseForm = {
  id: null, nome: '', categoria: 'propaganda', audience: 'todos', descricao: '', 
  imagem_url: '', link_url: '', conteudo_contrato: '', is_active: true, ordem_exibicao: 1,
  modelo_cobranca: 'assinatura', posicionamento: 'topo', status_financeiro: 'pago',
  dias_duracao: 15, limite_cliques: null, limite_conversoes: null,
  estatico: false, velocidade: 25 
};

const form = ref({ ...baseForm });

// LÓGICA DO PREVIEW & SANITIZAÇÃO (DEFENSIVE UI)
const previewDimensionsClass = computed(() => {
  const zone = form.value.posicionamento;
  if (zone === 'topo' || zone === 'rodape') return 'w-full h-16 sm:h-20'; 
  if (zone === 'lateral') return 'w-48 h-64'; 
  if (zone === 'direita') return 'w-64 h-full max-h-[400px]'; 
  return 'w-full h-32';
});

const dimensaoRecomendada = computed(() => {
  const zone = form.value.posicionamento;
  if (zone === 'topo' || zone === 'rodape') return '1200 x 150 px (Horizontal)';
  if (zone === 'lateral') return '600 x 600 px (Quadrado)';
  if (zone === 'direita') return '600 x 1200 px (Vertical)';
  return 'Automático';
});

// Filtro estrito de XSS para a URL da imagem no preview
const previewImageUrl = computed(() => {
  if (!form.value.imagem_url) return '';
  try {
    const url = new URL(form.value.imagem_url);
    return ['http:', 'https:'].includes(url.protocol) ? url.toString() : '';
  } catch {
    return '';
  }
});

const carregarParceiros = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/admin/crm/parceiros');
    // Validação estrita do payload (Impede quebra da iteração no template)
    parceiros.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
  } catch (error) {
    console.error('[API] Erro ao carregar malha de parceiros:', error);
  } finally {
    loading.value = false;
  }
};

const abrirModalCriacao = () => {
  isEditing.value = false;
  form.value = { ...baseForm };
  showModal.value = true;
  document.body.style.overflow = 'hidden'; 
};

const abrirModalEdicao = (item) => {
  isEditing.value = true;
  form.value = { 
    ...baseForm, 
    ...item,
    estatico: item.estatico ?? false,
    velocidade: item.velocidade ?? 25
  };
  showModal.value = true;
  document.body.style.overflow = 'hidden';
};

const fecharModal = () => {
  showModal.value = false;
  document.body.style.overflow = '';
};

const salvar = async () => {
  if (!form.value.nome) return alert("O título é obrigatório.");
  
  isSaving.value = true;
  try {
    // Sanitização de regras de negócio antes do Payload
    if (form.value.modelo_cobranca !== 'assinatura') form.value.dias_duracao = null;
    if (form.value.modelo_cobranca !== 'cpc') form.value.limite_cliques = null;
    if (form.value.modelo_cobranca !== 'cpa') form.value.limite_conversoes = null;

    if (isEditing.value) {
      await axios.put(`/api/v1/admin/crm/parceiros/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/v1/admin/crm/parceiros', form.value);
    }
    fecharModal();
    carregarParceiros();
  } catch (error) {
    if (error.response?.status === 422) {
      // Extração defensiva de erros de validação
      const errors = error.response.data.errors;
      const firstError = errors ? Object.values(errors)[0][0] : 'Dados inválidos detectados pelo servidor.';
      alert(`Validação Rejeitada: ${firstError}`);
    } else {
      alert('Falha crítica na comunicação com a infraestrutura de AdTech.');
    }
  } finally {
    isSaving.value = false;
  }
};

const excluirParceiro = async (id) => {
  if (!confirm('Deseja realmente desligar e apagar este anúncio permanentemente?')) return;
  try {
    await axios.delete(`/api/v1/admin/crm/parceiros/${id}`);
    carregarParceiros();
  } catch (error) {
    alert('Erro ao excluir parceiro.');
  }
};

onMounted(carregarParceiros);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px;}
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }

@keyframes scrollVerticalUp {
  0% { transform: translateY(0); }
  100% { transform: translateY(-50%); }
}
.animate-scroll-vertical-up {
  animation-name: scrollVerticalUp;
  animation-timing-function: linear;
  animation-iteration-count: infinite;
  will-change: transform;
  transform: translateZ(0); 
}
@media (prefers-reduced-motion: reduce) {
  .animate-scroll-vertical-up { animation: none !important; overflow-y: auto !important; position: static !important; }
}
</style>