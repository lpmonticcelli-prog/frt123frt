<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-bold text-gray-900">Editar Frete #{{ route.params.id }}</h2>
      <button @click="$router.push({ name: 'EmbarcadorDashboard' })" class="text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">
        &larr; Voltar ao Painel
      </button>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      
      <div v-if="pageLoading" class="flex justify-center items-center py-12">
        <div class="text-gray-500 font-bold">Buscando dados e malha logística...</div>
      </div>

      <div v-else>
        <div v-if="message.text" :class="`m-6 p-4 rounded-md text-sm font-bold ${message.type === 'success' ? 'bg-green-50 text-green-800 border-l-4 border-green-500' : 'bg-red-50 text-red-800 border-l-4 border-red-500'}`">
          {{ message.text }}
        </div>

        <form @submit.prevent="updateCarga" class="p-6 space-y-8">
          
          <div>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2">Informações da Mercadoria</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Produto</label>
                <input v-model="form.produto" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Espécie / Embalagem</label>
                <select v-model="form.especie" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white" required>
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
                <label class="block text-sm font-bold text-gray-700 mb-1">Peso Bruto (KG)</label>
                <input 
                    v-model="formVisual.peso_kg" 
                    v-maska
                    data-maska="9.99#,##" 
                    data-maska-tokens="9:[0-9]:repeated" 
                    data-maska-reversed="true"
                    @maska="formUnmasked.peso_kg = $event.detail.unmasked"
                    type="text" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm" 
                    required
                >
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Cubagem (m³)</label>
                <input 
                    v-model="formVisual.cubagem_m3" 
                    v-maska
                    data-maska="9.99#,##" 
                    data-maska-tokens="9:[0-9]:repeated" 
                    data-maska-reversed="true"
                    @maska="formUnmasked.cubagem_m3 = $event.detail.unmasked"
                    type="text" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                >
              </div>
              <div>
                 <label class="block text-sm font-bold text-gray-700 mb-1">Valor do Frete (R$)</label>
                 <input 
                    v-model="formVisual.valor_frete" 
                    v-maska
                    data-maska="9.99#,##" 
                    data-maska-tokens="9:[0-9]:repeated" 
                    data-maska-reversed="true"
                    @maska="formUnmasked.valor_frete = $event.detail.unmasked"
                    type="text" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm font-bold text-blue-900 bg-blue-50" 
                    required
                 >
              </div>
            </div>
          </div>

          <div>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2">Requisitos Logísticos</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Veículo Exigido</label>
                <select v-model="form.tipo_veiculo" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white" required>
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
                <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Carroceria</label>
                <select v-model="form.tipo_carroceria" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white" required>
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

          <div>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2">Rota da Carga</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">UF Origem</label>
                <select v-model="form.uf_origem" @change="carregarCidades(form.uf_origem, 'origem')" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white" required>
                  <option value="" disabled>Estado</option>
                  <option v-for="uf in ufs" :key="uf.sigla" :value="uf.sigla">{{ uf.sigla }}</option>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Cidade de Origem</label>
                <select v-model="form.cidade_origem" :disabled="!form.uf_origem || loadingCidadesOrigem" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white disabled:bg-gray-100" required>
                  <option value="" disabled>{{ loadingCidadesOrigem ? 'Carregando cidades...' : 'Selecione a cidade' }}</option>
                  <option v-for="cidade in cidadesOrigem" :key="cidade.id" :value="cidade.nome">{{ cidade.nome }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">UF Destino</label>
                <select v-model="form.uf_destino" @change="carregarCidades(form.uf_destino, 'destino')" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white" required>
                  <option value="" disabled>Estado</option>
                  <option v-for="uf in ufs" :key="uf.sigla" :value="uf.sigla">{{ uf.sigla }}</option>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Cidade de Destino</label>
                <select v-model="form.cidade_destino" :disabled="!form.uf_destino || loadingCidadesDestino" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white disabled:bg-gray-100" required>
                  <option value="" disabled>{{ loadingCidadesDestino ? 'Carregando cidades...' : 'Selecione a cidade' }}</option>
                  <option v-for="cidade in cidadesDestino" :key="cidade.id" :value="cidade.nome">{{ cidade.nome }}</option>
                </select>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                 <label class="block text-sm font-bold text-gray-700 mb-1">Data de Coleta</label>
                 <input v-model="form.data_coleta" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm" required>
              </div>
              <div>
                 <label class="block text-sm font-bold text-gray-700 mb-1">Previsão de Entrega (Opcional)</label>
                 <input v-model="form.data_entrega_prevista" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
              </div>
              <div>
                 <label class="block text-sm font-bold text-gray-700 mb-1">Distância Estimada (KM)</label>
                 <input v-model.number="form.distancia_km" type="number" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Opcional">
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-200 flex justify-end space-x-4">
            <button type="button" @click="$router.push({ name: 'EmbarcadorDashboard' })" class="px-6 py-2 border border-gray-300 text-gray-700 font-bold rounded-md hover:bg-gray-50 focus:outline-none transition-colors">
              Cancelar
            </button>
            <button 
              type="submit" 
              :disabled="submitLoading"
              class="px-6 py-2 bg-blue-600 text-white font-bold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
            >
              {{ submitLoading ? 'Salvando...' : 'Salvar Alterações' }}
            </button>
          </div>

        </form>
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