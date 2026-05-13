<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
      <h2 class="text-xl font-bold text-gray-800">Publicar Novo Frete</h2>
      <button @click="$router.push({ name: 'EmbarcadorDashboard' })" class="text-sm font-bold text-blue-600 hover:text-blue-800">
        &larr; Voltar ao Painel
      </button>
    </div>

    <div v-if="auth.user?.status === 'pending'" class="bg-amber-50 border-l-4 border-amber-500 p-4 m-6 rounded">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-amber-700 font-bold">
            Sua conta está em análise pendente. Você pode preencher o rascunho, mas a publicação está bloqueada até a aprovação.
          </p>
        </div>
      </div>
    </div>

    <form @submit.prevent="submitCarga" class="p-6 space-y-8">
      
      <div>
        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b pb-2">Informações da Mercadoria</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-1">Produto</label>
            <input v-model="form.produto" type="text" placeholder="Ex: Soja a granel" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm" required>
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
                placeholder="Ex: 32000,00" 
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
                placeholder="Ex: 45,50" 
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
                placeholder="Ex: 4.500,00" 
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
            <select v-model="form.uf_origem" @change="carregarCidades('origem')" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white" required>
              <option value="" disabled>Estado</option>
              <option v-for="uf in ufs" :key="uf.uf" :value="uf.uf">{{ uf.nome }} ({{ uf.uf }})</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-1">Cidade de Origem</label>
            <select v-model="form.cidade_origem" :disabled="!form.uf_origem || loadingCidadesOrigem" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white disabled:bg-gray-100" required>
              <option value="" disabled>{{ loadingCidadesOrigem ? 'Carregando cidades...' : 'Selecione a cidade' }}</option>
              <option v-for="cidade in cidadesOrigem" :key="cidade" :value="cidade">{{ cidade }}</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">UF Destino</label>
            <select v-model="form.uf_destino" @change="carregarCidades('destino')" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white" required>
              <option value="" disabled>Estado</option>
              <option v-for="uf in ufs" :key="uf.uf" :value="uf.uf">{{ uf.nome }} ({{ uf.uf }})</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-1">Cidade de Destino</label>
            <select v-model="form.cidade_destino" :disabled="!form.uf_destino || loadingCidadesDestino" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm bg-white disabled:bg-gray-100" required>
              <option value="" disabled>{{ loadingCidadesDestino ? 'Carregando cidades...' : 'Selecione a cidade' }}</option>
              <option v-for="cidade in cidadesDestino" :key="cidade" :value="cidade">{{ cidade }}</option>
            </select>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
             <label class="block text-sm font-bold text-gray-700 mb-1">Data de Coleta</label>
             <input v-model="form.data_coleta" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm" required>
          </div>
          <div>
             <label class="block text-sm font-bold text-gray-700 mb-1">Data de Entrega Prevista</label>
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
          :disabled="isSubmitting || auth.user?.status === 'pending'"
          class="px-6 py-2 bg-blue-600 text-white font-bold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
        >
          {{ isSubmitting ? 'Publicando...' : 'Publicar Carga no Mural' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';

const auth = useAuthStore();
const router = useRouter();

// Payload Base
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

// UI State para Máscaras
const formVisual = ref({ peso_kg: '', cubagem_m3: '', valor_frete: '' });
const formUnmasked = ref({ peso_kg: '', cubagem_m3: '', valor_frete: '' });
const isSubmitting = ref(false);

// Localidades Dinâmicas
const ufs = ref([]);
const cidadesOrigem = ref([]);
const cidadesDestino = ref([]);
const loadingCidadesOrigem = ref(false);
const loadingCidadesDestino = ref(false);

/**
 * 1. Inicialização: Busca UFs da base local
 */
onMounted(async () => {
  try {
    const res = await axios.get('/api/v1/localidades/estados');
    ufs.value = res.data;
  } catch (e) {
    console.error('Erro crítico ao carregar estados:', e);
  }
});

/**
 * 2. Busca de Cidades (Cache em SessionStorage para performance extrema)
 */
const carregarCidades = async (tipo) => {
  const isOrigem = tipo === 'origem';
  const uf = isOrigem ? form.value.uf_origem : form.value.uf_destino;
  
  if (!uf) return;

  // Reset do campo cidade
  if (isOrigem) form.value.cidade_origem = '';
  else form.value.cidade_destino = '';

  const cacheKey = `cidades_local_${uf}`;
  const cached = sessionStorage.getItem(cacheKey);

  if (cached) {
    if (isOrigem) cidadesOrigem.value = JSON.parse(cached);
    else cidadesDestino.value = JSON.parse(cached);
    return;
  }

  try {
    if (isOrigem) loadingCidadesOrigem.value = true;
    else loadingCidadesDestino.value = true;

    // Bate na API local (ajustada para o seu LocalidadeController)
    const res = await axios.get(`/api/v1/localidades/estados/${uf}/municipios`);
    const nomes = res.data.map(c => c.nome).sort();

    if (isOrigem) cidadesOrigem.value = nomes;
    else cidadesDestino.value = nomes;

    sessionStorage.setItem(cacheKey, JSON.stringify(nomes));
  } catch (error) {
    console.error(`Erro ao carregar cidades de ${uf}:`, error);
  } finally {
    if (isOrigem) loadingCidadesOrigem.value = false;
    else loadingCidadesDestino.value = false;
  }
};

/**
 * 3. Sanitização de Valores para o Banco de Dados (Float/Decimal)
 */
const formatStringToFloat = (val) => val ? parseFloat(val) / 100 : null;

/**
 * 4. Submissão Atómica
 */
const submitCarga = async () => {
  if (auth.user?.status === 'pending') return;

  isSubmitting.value = true;
  
  const payload = {
    ...form.value,
    peso_kg: formatStringToFloat(formUnmasked.value.peso_kg),
    cubagem_m3: formatStringToFloat(formUnmasked.value.cubagem_m3),
    valor_frete: formatStringToFloat(formUnmasked.value.valor_frete)
  };

  try {
    await axios.get('/sanctum/csrf-cookie');
    await axios.post('/api/v1/embarcador/cargas', payload);
    
    alert('Carga publicada com sucesso!');
    router.push({ name: 'EmbarcadorDashboard' });
  } catch (error) {
    if (error.response?.status === 422) {
      alert('Erro de Validação: ' + Object.values(error.response.data.errors).flat().join('\n'));
    } else {
      alert(error.response?.data?.message || 'Falha na comunicação com o servidor.');
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>