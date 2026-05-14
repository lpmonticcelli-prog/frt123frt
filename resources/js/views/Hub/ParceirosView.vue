<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
      <h2 class="text-2xl font-black text-slate-800 tracking-tight">Parceiros Estratégicos</h2>
      <p class="text-slate-500 mt-1">Conecte o seu sistema ou aceda a benefícios exclusivos da nossa rede.</p>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="space-y-6 animate-fade-in">
      
      <div v-if="propagandas.length > 0" class="grid grid-cols-1 gap-6">
        <div 
          v-for="banner in propagandas" :key="banner.id" 
          @click="acionarParceiro(banner)"
          class="relative w-full h-48 md:h-64 rounded-xl overflow-hidden shadow-md cursor-pointer group"
        >
          <img v-if="banner.imagem_url" :src="banner.imagem_url" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="Banner Parceiro">
          <div v-else class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-900 to-slate-800 flex items-center justify-center">
            <span class="text-white font-black text-2xl opacity-50">{{ banner.nome }}</span>
          </div>
          <div class="absolute inset-0 bg-black bg-opacity-30 group-hover:bg-opacity-10 transition-all"></div>
          <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black to-transparent">
            <h3 class="text-white font-black text-xl shadow-sm">{{ banner.nome }}</h3>
            <p class="text-gray-200 text-sm font-medium mt-1">{{ banner.descricao }}</p>
          </div>
          <div class="absolute top-4 right-4 bg-yellow-400 text-yellow-900 text-[10px] font-black px-2 py-1 rounded uppercase tracking-widest shadow-sm">
            Parceiro
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <div v-if="isEmbarcador" class="bg-white p-6 rounded-xl border border-slate-200 border-l-4 border-l-blue-600 shadow-sm hover:shadow-md transition">
          <h3 class="font-black text-lg mb-2 text-slate-800">Integração API Rest</h3>
          <p class="text-slate-600 text-sm mb-4 leading-relaxed">Gere a sua chave Bearer Token para injetar cargas automaticamente do seu sistema (SAP, TOTVS) para a 123fretei.</p>
          <button class="text-blue-600 font-bold text-sm hover:underline flex items-center gap-1">
            Gerar Token de Produção &rarr;
          </button>
        </div>

        <div v-for="item in servicosEContratos" :key="item.id" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between" :class="item.categoria === 'contrato_comercial' ? 'border-l-4 border-l-emerald-500' : 'border-l-4 border-l-slate-400'">
          <div>
            <div class="flex items-center gap-3 mb-3">
              <img v-if="item.imagem_url" :src="item.imagem_url" class="h-8 w-8 rounded-full object-cover border border-gray-200">
              <h3 class="font-black text-lg text-slate-800">{{ item.nome }}</h3>
            </div>
            <p class="text-slate-600 text-sm mb-4 leading-relaxed">{{ item.descricao }}</p>
          </div>
          
          <div class="mt-4 border-t border-slate-100 pt-4">
            <button v-if="item.categoria === 'contrato_comercial'" @click="abrirContrato(item)" class="w-full bg-emerald-50 text-emerald-700 font-bold text-sm py-2 rounded border border-emerald-200 hover:bg-emerald-100 transition">
              Ler Termos do Acordo
            </button>
            <button v-else @click="acionarParceiro(item)" class="text-slate-700 font-bold text-sm hover:text-blue-600 transition flex items-center gap-1">
              Saber mais &rarr;
            </button>
          </div>
        </div>

      </div>
    </div>

    <div v-if="showModalContrato" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="fecharContrato"></div>
      
      <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200">
        <div class="bg-emerald-600 px-6 py-4 text-white">
          <h3 class="text-lg font-black uppercase tracking-tight">Acordo de Parceria</h3>
          <p class="text-xs text-emerald-100 mt-1">{{ contratoAtivo?.nome }}</p>
        </div>

        <div class="p-6 max-h-[60vh] overflow-y-auto custom-scrollbar bg-slate-50">
          <div class="prose prose-sm prose-slate max-w-none text-justify whitespace-pre-wrap font-medium text-slate-700 leading-relaxed">
            {{ contratoAtivo?.conteudo_contrato || 'Nenhum termo especificado.' }}
          </div>
        </div>

        <div class="bg-white px-6 py-4 flex justify-between items-center border-t border-slate-200">
          <button @click="fecharContrato" class="text-sm font-bold text-slate-500 hover:text-slate-700">Fechar</button>
          <button @click="aceitarContrato(contratoAtivo)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black py-2 px-6 rounded shadow transition-colors">
            Eu Aceito os Termos
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const isEmbarcador = computed(() => authStore.user?.role?.slug === 'embarcador');

const parceiros = ref([]);
const loading = ref(true);

const showModalContrato = ref(false);
const contratoAtivo = ref(null);

const propagandas = computed(() => parceiros.value.filter(p => p.categoria === 'propaganda'));
const servicosEContratos = computed(() => parceiros.value.filter(p => p.categoria === 'anuncio' || p.categoria === 'contrato_comercial'));

const carregarDados = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/hub/parceiros');
    parceiros.value = res.data;
  } catch (error) {
    console.error('Erro ao carregar parceiros do Hub:', error);
  } finally {
    loading.value = false;
  }
};

const registrarClique = async (id) => {
  try { await axios.post(`/api/v1/hub/parceiros/${id}/clique`); } 
  catch (e) { /* Silencioso para não interromper a UX do usuário */ }
};

const acionarParceiro = (item) => {
  registrarClique(item.id);
  if (item.link_url) {
    window.open(item.link_url, '_blank');
  }
};

const abrirContrato = (item) => {
  contratoAtivo.value = item;
  showModalContrato.value = true;
  registrarClique(item.id); // Registramos o clique como interesse de leitura
};

const fecharContrato = () => {
  showModalContrato.value = false;
  contratoAtivo.value = null;
};

const aceitarContrato = (item) => {
  // Num cenário completo, gravaríamos a assinatura na base de dados.
  alert(`Termos da parceria ${item.nome} aceites com sucesso!`);
  fecharContrato();
  if (item.link_url) window.open(item.link_url, '_blank');
};

onMounted(() => {
  carregarDados();
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>