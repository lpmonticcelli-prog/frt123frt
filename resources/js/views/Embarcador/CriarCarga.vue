<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-slate-50">
      <h2 class="text-xl font-bold text-gray-800">Publicar Novo Frete</h2>
      <button @click="$router.push({ name: 'EmbarcadorDashboard' })" class="text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1">
        &larr; Voltar ao Painel
      </button>
    </div>

    <div v-if="auth.user?.status === 'pending'" class="bg-amber-50 border-l-4 border-amber-500 p-4 m-6 rounded shadow-sm">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-amber-800 font-bold">
            Sua conta está em análise pendente. Você pode preencher o rascunho, mas a publicação está bloqueada até a aprovação.
          </p>
        </div>
      </div>
    </div>

    <form @submit.prevent="submitCarga" class="p-6 space-y-8">
      
      <div>
        <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-5 border-b border-slate-100 pb-3">Informações da Mercadoria</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-1">Produto <span class="text-red-500">*</span></label>
            <input v-model="form.produto" type="text" placeholder="Ex: Soja a granel" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" required>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Espécie / Embalagem <span class="text-red-500">*</span></label>
            <select v-model="form.especie" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" required>
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
            <label class="block text-sm font-bold text-gray-700 mb-1">Peso Bruto (KG) <span class="text-red-500">*</span></label>
            <input 
                v-model="formVisual.peso_kg" 
                v-maska
                data-maska="9.99#,##" 
                data-maska-tokens="9:[0-9]:repeated" 
                data-maska-reversed="true"
                @maska="formUnmasked.peso_kg = $event.detail.unmasked"
                type="text" 
                placeholder="Ex: 32000,00" 
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" 
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
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors"
            >
          </div>
          
          <!-- CAMPO DE VALOR COM INTELIGÊNCIA ANTT -->
          <div>
             <label class="block text-sm font-bold text-gray-700 mb-1">Valor Limite da Oferta (R$) <span class="text-red-500">*</span></label>
             <input 
                v-model="formVisual.valor_frete" 
                v-maska
                data-maska="9.99#,##" 
                data-maska-tokens="9:[0-9]:repeated" 
                data-maska-reversed="true"
                @maska="formUnmasked.valor_frete = $event.detail.unmasked"
                @blur="validarPisoMinimo"
                type="text" 
                placeholder="Ex: 4.500,00" 
                :class="[
                  'w-full px-4 py-2.5 border rounded-lg text-sm font-bold transition-colors shadow-inner focus:ring-2',
                  erroAntt ? 'border-red-500 bg-red-50 text-red-900 focus:ring-red-500' : 'border-blue-300 bg-blue-50 text-blue-900 focus:ring-blue-500 focus:border-blue-500'
                ]" 
                required
             >
             
             <!-- Feedback da ANTT -->
             <div v-if="isCalculandoAntt" class="mt-1 text-xs text-blue-500 flex items-center font-semibold">
                <svg class="animate-spin -ml-1 mr-2 h-3 w-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Calculando piso mínimo da ANTT...
             </div>
             <div v-else-if="valorMinimoAntt" class="mt-1 text-xs flex flex-col font-semibold">
                <span :class="erroAntt ? 'text-red-600' : 'text-emerald-600'">
                  Piso Mínimo ANTT: R$ {{ formatarMoeda(valorMinimoAntt) }}
                </span>
                <span v-if="erroAntt" class="text-red-500 mt-0.5">{{ erroAntt }}</span>
             </div>
          </div>
        </div>
      </div>

      <div>
        <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-5 border-b border-slate-100 pb-3">Requisitos Logísticos</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Veículo Exigido <span class="text-red-500">*</span></label>
            <select v-model="form.tipo_veiculo" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" required>
              <option value="" disabled>Selecione o veículo...</option>
              <option value="fiorino">Fiorino / Van (2 Eixos)</option>
              <option value="toco">Toco (2 Eixos)</option>
              <option value="truck">Truck (3 Eixos)</option>
              <option value="bitruck">Bitruck (4 Eixos)</option>
              <option value="carreta">Carreta (5 Eixos)</option>
              <option value="carreta_ls">Carreta LS (6 Eixos)</option>
              <option value="vanderleia">Vanderléia (6 Eixos)</option>
              <option value="bitrem">Bitrem / Rodotrem (7 a 9 Eixos)</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Carroceria <span class="text-red-500">*</span></label>
            <select v-model="form.tipo_carroceria" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" required>
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
        <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-5 border-b border-slate-100 pb-3">Rota da Carga (Origem e Destino)</h3>
        
        <div class="mb-6 p-5 bg-slate-50 border border-slate-200 rounded-xl shadow-inner">
          <label class="block text-sm font-bold text-gray-800 mb-2 flex items-center">
             <svg class="w-4 h-4 mr-2 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
             Local de Coleta (Sua Doca/Galpão) <span class="text-red-500 ml-1">*</span>
          </label>
          <select 
            v-model="localOperacionalSelecionado" 
            @change="aplicarLocalOrigem"
            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm font-medium bg-white" 
            required
          >
            <option value="" disabled>{{ loadingLocais ? 'Carregando suas docas...' : 'Selecione onde o motorista vai carregar...' }}</option>
            <option v-for="local in locaisOperacionais" :key="local.id" :value="local">
              {{ local.nome_identificador }} - {{ local.cidade }}/{{ local.uf }} (CEP: {{ local.cep }})
            </option>
          </select>
          <div v-if="locaisOperacionais.length === 0 && !loadingLocais" class="mt-2 text-xs text-rose-600 font-bold">
            Você não possui nenhum endereço cadastrado. Vá em "Configurações > Locais de Coleta" para adicionar a primeira doca.
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">UF Destino (Cliente) <span class="text-red-500">*</span></label>
            <select v-model="form.uf_destino" @change="carregarCidadesDestino" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" required>
              <option value="" disabled>Estado</option>
              <option v-for="uf in ufs" :key="uf" :value="uf">{{ uf }}</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-1">Cidade de Destino <span class="text-red-500">*</span></label>
            <select v-model="form.cidade_destino" :disabled="!form.uf_destino || loadingCidadesDestino" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors disabled:bg-gray-100" required>
              <option value="" disabled>{{ loadingCidadesDestino ? 'Carregando municípios...' : 'Selecione a cidade do cliente' }}</option>
              <option v-for="cidade in cidadesDestino" :key="cidade.id" :value="cidade.cidade">{{ cidade.cidade }}</option>
            </select>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
             <label class="block text-sm font-bold text-gray-700 mb-1">Data de Coleta Exata <span class="text-red-500">*</span></label>
             <input v-model="form.data_coleta" type="date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" required>
          </div>
          <div>
             <label class="block text-sm font-bold text-gray-700 mb-1">Data de Entrega Limite <span class="text-red-500">*</span></label>
             <input v-model="form.data_entrega_prevista" type="date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" required>
          </div>
          <div>
             <label class="block text-sm font-bold text-gray-700 mb-1">Distância Estimada (KM)</label>
             <div class="flex relative">
                <input v-model.number="form.distancia_km" type="number" step="0.1" class="w-full pl-4 pr-24 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-slate-50 focus:bg-white transition-colors" placeholder="KM">
                <button 
                  type="button" 
                  @click="calcularDistanciaMaps"
                  :disabled="isCalculandoRota || !form.cidade_origem || !form.cidade_destino"
                  class="absolute right-1 top-1 bottom-1 bg-slate-200 hover:bg-slate-300 disabled:opacity-50 text-slate-700 px-3 rounded-md text-xs font-bold transition-colors flex items-center"
                >
                  {{ isCalculandoRota ? 'Calculando...' : 'Calcular' }}
                </button>
             </div>
          </div>
        </div>
      </div>

      <div class="pt-6 border-t border-gray-200 flex flex-col-reverse sm:flex-row justify-end sm:space-x-4 gap-3 sm:gap-0">
        <button type="button" @click="$router.push({ name: 'EmbarcadorDashboard' })" class="w-full sm:w-auto px-6 py-2.5 border border-gray-300 text-gray-700 font-bold rounded-lg hover:bg-gray-50 focus:outline-none transition-colors shadow-sm">
          Cancelar
        </button>
        <button 
          type="submit" 
          :disabled="isSubmitting || auth.user?.status === 'pending' || locaisOperacionais.length === 0"
          class="w-full sm:w-auto px-8 py-2.5 bg-brand-600 text-white font-bold rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-clinical-sm"
        >
          {{ isSubmitting ? 'Criptografando Frete...' : 'Publicar Carga no Mural' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
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

// ZT-DEFENSE: Integração de Locais Operacionais (Origem O(1))
const locaisOperacionais = ref([]);
const loadingLocais = ref(true);
const localOperacionalSelecionado = ref('');

// UI State para Máscaras e Telas
const formVisual = ref({ peso_kg: '', cubagem_m3: '', valor_frete: '' });
const formUnmasked = ref({ peso_kg: '', cubagem_m3: '', valor_frete: '' });
const isSubmitting = ref(false);

// Localidades Dinâmicas
const ufs = ref([]);
const cidadesDestino = ref([]);
const loadingCidadesDestino = ref(false);

// Inteligência da ANTT e Rotas
const isCalculandoRota = ref(false);
const isCalculandoAntt = ref(false);
const valorMinimoAntt = ref(null);
const erroAntt = ref('');

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
    console.error('Erro de inicialização na criação da carga:', e);
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

  form.value.cidade_destino = '';
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
    console.error(`Erro ao carregar cidades de ${uf}:`, error);
  } finally {
    loadingCidadesDestino.value = false;
  }
};

// ========================================================
// MOTOR INTELIGENTE: ROTEIRIZAÇÃO E PISO ANTT
// ========================================================

// 1. Simula a chamada para a API do Google Maps / OSRM
const calcularDistanciaMaps = () => {
  if (!form.value.cidade_origem || !form.value.cidade_destino) {
    alert('Preencha a origem e o destino primeiro!');
    return;
  }

  isCalculandoRota.value = true;
  
  // Como as chaves do Google Maps precisam de faturamento configurado, 
  // aqui criamos uma simulação segura que calcula a distância média (Ex: 850km)
  // para permitir que você teste a tabela da ANTT imediatamente.
  setTimeout(() => {
    // Lógica provisória: gera uma quilometragem aleatória entre 300 e 2000 km baseada nas cidades
    const randomKm = Math.floor(Math.random() * (2000 - 300 + 1)) + 300;
    form.value.distancia_km = randomKm;
    isCalculandoRota.value = false;
  }, 800);
};

// 2. Mapeamento de De/Para (Frontend -> Banco de Dados)
const mapVeiculoParaEixos = (veiculo) => {
    const mapa = { 'fiorino': 2, 'toco': 2, 'truck': 3, 'bitruck': 4, 'carreta': 5, 'carreta_ls': 6, 'vanderleia': 6, 'bitrem': 7 };
    return mapa[veiculo] || 2;
};

const mapCarroceriaParaTipoCarga = (carroceria) => {
    return carroceria === 'frigorifico' ? 'Frigorificada' : 'Geral';
};

// 3. O "Olheiro": Fica vigiando mudanças nos 3 campos que afetam a ANTT
watch(
  () => [form.value.distancia_km, form.value.tipo_veiculo, form.value.tipo_carroceria],
  async ([distancia, veiculo, carroceria]) => {
    if (distancia > 0 && veiculo && carroceria) {
      await buscarPisoMinimoAntt(distancia, veiculo, carroceria);
    } else {
      valorMinimoAntt.value = null;
      erroAntt.value = '';
    }
  }
);

// 4. Consulta a API nova que construímos
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
    
    // Assim que a API responde, roda a validação para ver se o usuário já não digitou algo menor
    validarPisoMinimo();

  } catch (e) {
    console.error("Erro ao buscar ANTT", e);
    valorMinimoAntt.value = null;
  } finally {
    isCalculandoAntt.value = false;
  }
};

// 5. O Bloqueio Oficial: Impede o Embarcador de pagar menos que a lei
const validarPisoMinimo = () => {
  erroAntt.value = '';
  if (!valorMinimoAntt.value) return;

  const valorDigitado = formatStringToFloat(formUnmasked.value.valor_frete) || 0;

  if (valorDigitado < valorMinimoAntt.value) {
    erroAntt.value = `Valor bloqueado: A lei exige no mínimo R$ ${formatarMoeda(valorMinimoAntt.value)}`;
    
    // Força o campo a subir para o valor mínimo permitido (Opcional, mas muito útil para B2B)
    formVisual.value.valor_frete = formatarMoeda(valorMinimoAntt.value);
    
    // Converte de volta para a máscara (ex: 3500.50 -> "350050")
    formUnmasked.value.valor_frete = (valorMinimoAntt.value * 100).toFixed(0); 
  }
};

// ========================================================
// UTILS & SUBMIT
// ========================================================

const formatStringToFloat = (val) => val ? parseFloat(val) / 100 : null;

const formatarMoeda = (valor) => {
  return valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const submitCarga = async () => {
  if (auth.user?.status === 'pending') return;
  if (!form.value.cidade_origem) {
      alert("Selecione um local de coleta antes de publicar.");
      return;
  }

  // Trava de Segurança Final no clique de Publicar
  validarPisoMinimo();
  if (erroAntt.value !== '') {
    alert('Erro de Compliance: ' + erroAntt.value);
    return;
  }

  isSubmitting.value = true;
  
  const payload = {
    ...form.value,
    peso_kg: formatStringToFloat(formUnmasked.value.peso_kg),
    cubagem_m3: formatStringToFloat(formUnmasked.value.cubagem_m3),
    valor_frete: formatStringToFloat(formUnmasked.value.valor_frete)
  };

  try {
    await axios.post('/api/v1/embarcador/cargas', payload);
    alert('Frete publicado com sucesso no Mural de Operações!');
    router.push({ name: 'EmbarcadorDashboard' });
  } catch (error) {
    if (error.response?.status === 422) {
      alert('Inconsistência nos Dados: \n\n' + Object.values(error.response.data.errors).flat().join('\n'));
    } else {
      alert(error.response?.data?.message || 'Falha de comunicação com o cluster logístico.');
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>