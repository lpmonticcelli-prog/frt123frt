<template>
  <div class="w-full relative min-h-screen bg-slate-50 pb-12 pt-8 px-4 sm:px-6">
    <div class="max-w-4xl mx-auto">
      
      <!-- HEADER -->
      <div class="flex justify-between items-center bg-white p-6 rounded-t-2xl border-b border-slate-200 shadow-sm">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Editar Frete #{{ route.params.id }}</h2>
          <p class="text-sm text-slate-500 mt-1 font-medium">Atualize os dados e requisitos logísticos da carga.</p>
        </div>
        <button @click="$router.push({ name: 'EmbarcadorDashboard' })" class="text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-200 rounded-lg px-4 py-2 border border-slate-200 hover:bg-slate-50 shadow-sm">
          &larr; Voltar
        </button>
      </div>

      <!-- CONTAINER PRINCIPAL -->
      <div class="bg-white rounded-b-2xl shadow-sm border border-t-0 border-slate-200 overflow-hidden">
        
        <!-- ESTADO: CARREGANDO -->
        <div v-if="pageLoading" class="flex flex-col justify-center items-center py-20">
          <svg class="w-10 h-10 animate-spin text-[#ff5500] mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <div class="text-slate-500 font-bold tracking-wide">Sincronizando dados da malha logística...</div>
        </div>

        <div v-else>
          <!-- MENSAGENS DE SUCESSO/ERRO -->
          <div v-if="message.text" :class="`m-6 p-4 rounded-xl text-sm font-bold shadow-sm border ${message.type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200'}`">
            {{ message.text }}
          </div>

          <form @submit.prevent="updateCarga" class="p-6 sm:p-8 space-y-10">
            
            <!-- SEÇÃO 1: MERCADORIA -->
            <div>
              <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 pb-3">Informações da Mercadoria</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                  <label class="block text-sm font-bold text-slate-700 mb-2">Produto</label>
                  <input v-model="form.produto" type="text" required class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
                </div>
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">Espécie / Embalagem</label>
                  <select v-model="form.especie" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm" required>
                    <option value="" disabled>Selecione...</option>
                    <option value="caixas">Caixas</option>
                    <option value="paletes">Paletes</option>
                    <option value="sacaria">Sacaria</option>
                    <option value="granel">Granel</option>
                    <option value="tambores">Tambores</option>
                    <option value="outro">Outro</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">Peso Bruto (KG)</label>
                  <input 
                      v-model="formVisual.peso_kg" 
                      v-maska
                      data-maska="9.99#,##" 
                      data-maska-tokens="9:[0-9]:repeated" 
                      data-maska-reversed="true"
                      @maska="formUnmasked.peso_kg = $event.detail.unmasked"
                      type="text" 
                      class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm" 
                      required
                  >
                </div>
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">Cubagem (m³)</label>
                  <input 
                      v-model="formVisual.cubagem_m3" 
                      v-maska
                      data-maska="9.99#,##" 
                      data-maska-tokens="9:[0-9]:repeated" 
                      data-maska-reversed="true"
                      @maska="formUnmasked.cubagem_m3 = $event.detail.unmasked"
                      type="text" 
                      class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm"
                  >
                </div>
                <div>
                   <label class="block text-sm font-bold text-slate-700 mb-2">Valor do Frete (R$)</label>
                   <input 
                      v-model="formVisual.valor_frete" 
                      v-maska
                      data-maska="9.99#,##" 
                      data-maska-tokens="9:[0-9]:repeated" 
                      data-maska-reversed="true"
                      @maska="formUnmasked.valor_frete = $event.detail.unmasked"
                      type="text" 
                      class="w-full px-4 py-3 border border-[#035D29]/30 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm font-black text-[#035D29] bg-emerald-50/50 shadow-inner transition-colors" 
                      required
                   >
                </div>
              </div>
            </div>

            <!-- SEÇÃO 2: REQUISITOS LOGÍSTICOS -->
            <div>
              <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 pb-3">Requisitos Logísticos</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">Tipo de Veículo Exigido</label>
                  <select v-model="form.tipo_veiculo" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm" required>
                    <option value="" disabled>Selecione o veículo...</option>
                    <option value="fiorino">Fiorino / Van</option>
                    <option value="toco">Toco</option>
                    <option value="truck">Truck</option>
                    <option value="bitruck">Bitruck</option>
                    <option value="carreta">Carreta</option>
                    <option value="carreta_ls">Carreta LS</option>
                    <option value="vanderleia">Vanderléia</option>
                    <option value="bitrem">Bitrem / Rodotrem</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">Tipo de Carroceria</label>
                  <select v-model="form.tipo_carroceria" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm" required>
                    <option value="" disabled>Selecione a carroceria...</option>
                    <option value="bau">Baú Fechado</option>
                    <option value="sider">Sider</option>
                    <option value="aberta">Carroceria Aberta / Carga Seca</option>
                    <option value="graneleiro">Graneleiro</option>
                    <option value="frigorifico">Frigorífico</option>
                    <option value="prancha">Prancha</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- SEÇÃO 3: ROTA -->
            <div>
              <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 pb-3">Rota da Carga</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 p-6 bg-slate-50/50 border border-slate-200 rounded-2xl shadow-sm">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">UF Origem</label>
                  <select v-model="form.uf_origem" @change="carregarCidades(form.uf_origem, 'origem')" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-white shadow-sm" required>
                    <option value="" disabled>Estado</option>
                    <option v-for="uf in ufs" :key="uf.sigla" :value="uf.sigla">{{ uf.sigla }}</option>
                  </select>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-bold text-slate-700 mb-2">Cidade de Origem</label>
                  <select v-model="form.cidade_origem" :disabled="!form.uf_origem || loadingCidadesOrigem" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-white shadow-sm disabled:bg-slate-100 disabled:text-slate-400" required>
                    <option value="" disabled>{{ loadingCidadesOrigem ? 'Carregando cidades...' : 'Selecione a cidade' }}</option>
                    <option v-for="cidade in cidadesOrigem" :key="cidade.id" :value="cidade.nome">{{ cidade.nome }}</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">UF Destino</label>
                  <select v-model="form.uf_destino" @change="carregarCidades(form.uf_destino, 'destino')" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm" required>
                    <option value="" disabled>Estado</option>
                    <option v-for="uf in ufs" :key="uf.sigla" :value="uf.sigla">{{ uf.sigla }}</option>
                  </select>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-bold text-slate-700 mb-2">Cidade de Destino</label>
                  <select v-model="form.cidade_destino" :disabled="!form.uf_destino || loadingCidadesDestino" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm disabled:bg-slate-100 disabled:text-slate-400" required>
                    <option value="" disabled>{{ loadingCidadesDestino ? 'Carregando cidades...' : 'Selecione a cidade' }}</option>
                    <option v-for="cidade in cidadesDestino" :key="cidade.id" :value="cidade.nome">{{ cidade.nome }}</option>
                  </select>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                   <label class="block text-sm font-bold text-slate-700 mb-2">Data de Coleta</label>
                   <input v-model="form.data_coleta" type="date" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm" required>
                </div>
                <div>
                   <label class="block text-sm font-bold text-slate-700 mb-2">Previsão de Entrega</label>
                   <input v-model="form.data_entrega_prevista" type="date" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
                </div>
                <div>
                   <label class="block text-sm font-bold text-slate-700 mb-2">Distância Estimada (KM)</label>
                   <input v-model.number="form.distancia_km" type="number" step="0.1" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm" placeholder="Opcional">
                </div>
              </div>
            </div>

            <!-- AÇÕES FINAIS -->
            <div class="pt-8 mt-8 border-t border-slate-200 flex flex-col-reverse sm:flex-row justify-end sm:space-x-4 gap-3 sm:gap-0">
              <button type="button" @click="$router.push({ name: 'EmbarcadorDashboard' })" class="w-full sm:w-auto px-6 py-3 border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 focus:outline-none transition-colors shadow-sm">
                Cancelar
              </button>
              <button 
                type="submit" 
                :disabled="submitLoading"
                class="w-full sm:w-auto px-10 py-3 bg-[#035D29] text-white font-bold rounded-xl hover:bg-[#023818] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#035D29] disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-md"
              >
                {{ submitLoading ? 'Salvando...' : 'Salvar Alterações' }}
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

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

const formVisual = ref({
  peso_kg: '',
  cubagem_m3: '',
  valor_frete: ''
});

const formUnmasked = ref({
  peso_kg: '',
  cubagem_m3: '',
  valor_frete: ''
});

const pageLoading = ref(true);
const submitLoading = ref(false);
const message = ref({ type: '', text: '' });

const ufs = ref([]);
const cidadesOrigem = ref([]);
const cidadesDestino = ref([]);
const loadingCidadesOrigem = ref(false);
const loadingCidadesDestino = ref(false);

const carregarCidades = async (uf, tipo, isInitialLoad = false) => {
  if (!uf) return;
  
  if (tipo === 'origem') {
    loadingCidadesOrigem.value = true;
    if (!isInitialLoad) form.value.cidade_origem = '';
  } else {
    loadingCidadesDestino.value = true;
    if (!isInitialLoad) form.value.cidade_destino = '';
  }

  try {
    const response = await axios.get(`/api/v1/localidades/estados/${uf}/municipios`);
    if (tipo === 'origem') cidadesOrigem.value = response.data;
    else cidadesDestino.value = response.data;
  } catch (error) {
    console.error(`Erro ao carregar cidades para a UF ${uf}:`, error);
  } finally {
    if (tipo === 'origem') loadingCidadesOrigem.value = false;
    else loadingCidadesDestino.value = false;
  }
};

const formatFloatToString = (floatValue) => {
    if (!floatValue) return '';
    return (parseFloat(floatValue) * 100).toFixed(0);
};

const formatStringToFloat = (stringNumber) => {
  if (!stringNumber) return null;
  return parseFloat(stringNumber) / 100;
};

onMounted(async () => {
  try {
    const responseUFs = await axios.get('/api/v1/localidades/estados?orderBy=nome');
    ufs.value = responseUFs.data;

    const response = await axios.get(`/api/v1/embarcador/cargas/${route.params.id}`);
    const dataCarregada = response.data;
    
    if (dataCarregada.data_coleta) dataCarregada.data_coleta = dataCarregada.data_coleta.split('T')[0];
    if (dataCarregada.data_entrega_prevista) dataCarregada.data_entrega_prevista = dataCarregada.data_entrega_prevista.split('T')[0];
    
    form.value = { ...dataCarregada };
    
    formUnmasked.value.peso_kg = formatFloatToString(dataCarregada.peso_kg);
    formVisual.value.peso_kg = dataCarregada.peso_kg; 

    formUnmasked.value.cubagem_m3 = formatFloatToString(dataCarregada.cubagem_m3);
    formVisual.value.cubagem_m3 = dataCarregada.cubagem_m3;

    formUnmasked.value.valor_frete = formatFloatToString(dataCarregada.valor_frete);
    formVisual.value.valor_frete = dataCarregada.valor_frete;

    if (form.value.uf_origem) await carregarCidades(form.value.uf_origem, 'origem', true);
    if (form.value.uf_destino) await carregarCidades(form.value.uf_destino, 'destino', true);

  } catch (error) {
    message.value = { type: 'error', text: 'Erro ao carregar os dados. Verifique se a carga existe ou se você tem permissão.' };
  } finally {
    pageLoading.value = false;
  }
});

const updateCarga = async () => {
  submitLoading.value = true;
  message.value = { type: '', text: '' };

  const payload = {
    ...form.value,
    peso_kg: formatStringToFloat(formUnmasked.value.peso_kg),
    cubagem_m3: formatStringToFloat(formUnmasked.value.cubagem_m3),
    valor_frete: formatStringToFloat(formUnmasked.value.valor_frete)
  };

  try {
    await axios.put(`/api/v1/embarcador/cargas/${route.params.id}`, payload);
    message.value = { type: 'success', text: 'Carga atualizada com sucesso!' };
    
    setTimeout(() => {
        router.push({ name: 'EmbarcadorDashboard' });
    }, 1500);

  } catch (error) {
    if (error.response?.status === 422) {
      const errosDeValidacao = error.response.data.errors;
      let mensagemErro = 'Verifique os seguintes campos:\n';
      for (const campo in errosDeValidacao) {
        mensagemErro += `- ${errosDeValidacao[campo][0]}\n`;
      }
      message.value = { type: 'error', text: mensagemErro };
    } else {
      message.value = { type: 'error', text: error.response?.data?.message || 'Erro interno ao atualizar a carga.' };
    }
  } finally {
    submitLoading.value = false;
  }
};
</script>