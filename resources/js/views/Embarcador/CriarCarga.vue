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
              <option v-for="uf in ufs" :key="uf.sigla" :value="uf.sigla">{{ uf.sigla }}</option>
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
              <option v-for="uf in ufs" :key="uf.sigla" :value="uf.sigla">{{ uf.sigla }}</option>
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
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';

const auth = useAuthStore();
const router = useRouter();

// Campos sem máscara
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

// Campos com máscara (O que aparece pro usuário, ex: "4.500,00")
const formVisual = ref({
  peso_kg: '',
  cubagem_m3: '',
  valor_frete: ''
});

// Dados capturados sem a formatação visual
const formUnmasked = ref({
  peso_kg: '',
  cubagem_m3: '',
  valor_frete: ''
});

const isSubmitting = ref(false);

// ==========================================
// 1. DADOS ESTÁTICOS (ESTADOS) CHUMBADOS NA MEMÓRIA
// ==========================================
const ufs = [
  { sigla: 'AC' }, { sigla: 'AL' }, { sigla: 'AP' }, { sigla: 'AM' }, { sigla: 'BA' },
  { sigla: 'CE' }, { sigla: 'DF' }, { sigla: 'ES' }, { sigla: 'GO' }, { sigla: 'MA' },
  { sigla: 'MT' }, { sigla: 'MS' }, { sigla: 'MG' }, { sigla: 'PA' }, { sigla: 'PB' },
  { sigla: 'PR' }, { sigla: 'PE' }, { sigla: 'PI' }, { sigla: 'RJ' }, { sigla: 'RN' },
  { sigla: 'RS' }, { sigla: 'RO' }, { sigla: 'RR' }, { sigla: 'SC' }, { sigla: 'SP' },
  { sigla: 'SE' }, { sigla: 'TO' }
];

const cidadesOrigem = ref([]);
const cidadesDestino = ref([]);
const loadingCidadesOrigem = ref(false);
const loadingCidadesDestino = ref(false);

// ==========================================
// 2. BUSCA DE CIDADES COM FETCH NATIVO E CACHE
// ==========================================
const carregarCidades = async (tipo) => {
  const isOrigem = tipo === 'origem';
  const ufSelecionada = isOrigem ? form.value.uf_origem : form.value.uf_destino;
  
  if (!ufSelecionada) return;

  // Limpa a cidade anterior ao trocar de estado
  if (isOrigem) form.value.cidade_origem = '';
  else form.value.cidade_destino = '';

  const cacheKey = `cidades_ibge_${ufSelecionada}`;
  const cachedData = sessionStorage.getItem(cacheKey);

  // Se já buscou essa UF nesta sessão, pega da memória (0 ms de latência)
  if (cachedData) {
    if (isOrigem) cidadesOrigem.value = JSON.parse(cachedData);
    else cidadesDestino.value = JSON.parse(cachedData);
    return;
  }

  // Se não tem no cache, bate no IBGE com Fetch nativo
  try {
    if (isOrigem) loadingCidadesOrigem.value = true;
    else loadingCidadesDestino.value = true;

    const response = await fetch(`https://servicodados.ibge.gov.br/api/localidades/estados/${ufSelecionada}/municipios`);
    
    if (!response.ok) throw new Error('Falha na resposta do IBGE');
    
    const data = await response.json();
    const cidadesMapeadas = data?.map(c => c.nome).sort(); // Extrai apenas os nomes e organiza em ordem alfabética

    if (isOrigem) cidadesOrigem.value = cidadesMapeadas;
    else cidadesDestino.value = cidadesMapeadas;

    // Guarda na memória do navegador para a próxima vez
    sessionStorage.setItem(cacheKey, JSON.stringify(cidadesMapeadas));

  } catch (error) {
    console.error(`Erro ao carregar cidades de ${ufSelecionada}:`, error);
    alert('O sistema do IBGE está indisponível no momento. Tente selecionar o estado novamente em alguns segundos.');
  } finally {
    if (isOrigem) loadingCidadesOrigem.value = false;
    else loadingCidadesDestino.value = false;
  }
};

// Função para converter o padrão BR de moeda para o Float do Banco de Dados
const formatStringToFloat = (stringNumber) => {
  if (!stringNumber) return null;
  // Divide o "unmasked" (ex: 450000) por 100 para chegar a 4500.00
  return parseFloat(stringNumber) / 100;
};

const submitCarga = async () => {
  if (auth.user?.status === 'pending') {
    alert('Ação bloqueada: Sua conta não está aprovada para publicação.');
    return;
  }

  isSubmitting.value = true;
  
  // Monta o Payload convertendo as strings capturadas do Maska para Decimais
  const payload = {
    ...form.value,
    peso_kg: formatStringToFloat(formUnmasked.value.peso_kg),
    cubagem_m3: formatStringToFloat(formUnmasked.value.cubagem_m3),
    valor_frete: formatStringToFloat(formUnmasked.value.valor_frete)
  };

  try {
    // Interceptador para rotas protegidas
    await axios.get('/sanctum/csrf-cookie');
    
    // Dispara a requisição de publicação
    await axios.post('/api/cargas', payload);
    
    alert('Carga publicada com sucesso no Mural de Fretes!');
    router.push({ name: 'EmbarcadorDashboard' });

  } catch (error) {
    console.error('Erro ao salvar carga:', error);
    
    // Captura Erros de Validação do Backend (422 Unprocessable Entity)
    if (error.response && error.response.status === 422) {
      const errosDeValidacao = error.response.data.errors;
      let mensagemErro = 'Verifique os seguintes campos:\n';
      
      // Mapeia e junta todos os erros retornados pelo Laravel
      for (const campo in errosDeValidacao) {
        mensagemErro += `- ${errosDeValidacao[campo][0]}\n`;
      }
      alert(mensagemErro);
    } else {
      alert(error.response?.data?.message || 'Falha ao publicar carga. Erro de conexão com o servidor.');
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>