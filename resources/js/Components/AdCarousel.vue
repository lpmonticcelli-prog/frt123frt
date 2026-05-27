<template>
  <div class="relative w-full h-full overflow-hidden bg-surface-50 border border-surface-200 rounded-md shadow-inner group flex justify-center" aria-label="Espaço Publicitário Parceiros">
    
    <div v-if="loading" class="flex items-center justify-center h-full w-full">
      <svg class="w-5 h-5 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
    </div>
    
    <div v-else-if="!ads || ads.length === 0" class="flex flex-col items-center justify-center h-full w-full text-surface-400 p-2 text-center">
      <span class="text-[9px] font-black uppercase tracking-widest opacity-50 select-none">Espaço Publicitário</span>
    </div>
    
    <div 
      v-else 
      :class="configEstatico ? 'flex flex-col w-full h-full overflow-y-auto scrollbar-clinical' : 'flex flex-col w-full absolute top-0 animate-scroll-vertical-up group-hover:[animation-play-state:paused] focus-within:[animation-play-state:paused]'"
      :style="!configEstatico ? { animationDuration: `${configVelocidade}s` } : {}"
    >
      
      <div class="flex flex-col gap-3 p-2">
        <a 
          v-for="ad in ads" 
          :key="`ad-${ad.id}`" 
          :href="ad.link_url || '#'" 
          target="_blank" 
          rel="noopener noreferrer" 
          @click="registrarClique(ad)" 
          class="block w-full overflow-hidden rounded shadow-clinical-sm hover:shadow-clinical-md transition-shadow border border-surface-200 bg-white group/ad focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1 shrink-0"
          :title="ad.nome || 'Anúncio de Parceiro'"
        >
          <img v-if="ad.imagem_url" :src="ad.imagem_url" :alt="ad.nome || 'Publicidade'" class="w-full h-auto object-cover group-hover/ad:opacity-90 transition-opacity" loading="lazy" />
          <div v-else class="p-3 text-center">
            <p class="text-xs font-black text-surface-800 truncate">{{ ad.nome || 'Parceiro' }}</p>
            <p class="text-[9px] text-brand-600 font-bold uppercase mt-1 truncate">{{ ad.descricao || 'Saiba Mais' }}</p>
          </div>
        </a>
      </div>

      <div v-if="!configEstatico" class="flex flex-col gap-3 p-2" aria-hidden="true">
        <a 
          v-for="ad in ads" 
          :key="`dup-${ad.id}`" 
          :href="ad.link_url || '#'" 
          target="_blank" 
          rel="noopener noreferrer" 
          @click="registrarClique(ad)" 
          tabindex="-1"
          class="block w-full overflow-hidden rounded shadow-clinical-sm hover:shadow-clinical-md transition-shadow border border-surface-200 bg-white group/ad shrink-0"
        >
          <img v-if="ad.imagem_url" :src="ad.imagem_url" :alt="ad.nome || 'Publicidade'" class="w-full h-auto object-cover group-hover/ad:opacity-90 transition-opacity" loading="lazy" />
          <div v-else class="p-3 text-center">
            <p class="text-xs font-black text-surface-800 truncate">{{ ad.nome || 'Parceiro' }}</p>
            <p class="text-[9px] text-brand-600 font-bold uppercase mt-1 truncate">{{ ad.descricao || 'Saiba Mais' }}</p>
          </div>
        </a>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const props = defineProps({
  posicionamento: {
    type: String,
    required: true,
    validator: (value) => ['topo', 'lateral', 'rodape', 'direita'].includes(value)
  },
  // Fallbacks de segurança caso o banco não devolva as configurações
  estatico: {
    type: Boolean,
    default: false 
  },
  velocidade: {
    type: Number,
    default: 25 
  }
});

const ads = ref([]);
const loading = ref(true);
let abortController = null;

// Variáveis reativas controladas pelo payload do Banco de Dados
const configEstatico = ref(props.estatico);
const configVelocidade = ref(props.velocidade);

const fetchAds = async () => {
  loading.value = true;
  abortController = new AbortController();
  
  try {
    const response = await axios.get(`/api/v1/hub/parceiros?posicionamento=${props.posicionamento}`, {
      signal: abortController.signal
    });
    
    const payload = response.data?.data || response.data;
    ads.value = Array.isArray(payload) ? payload : [];

    // INVERSÃO DE CONTROLE: O Backend passa a ditar a renderização da GPU
    if (ads.value.length > 0) {
      const leaderAd = ads.value[0]; // A zona herda a configuração do banner vencedor do leilão
      
      const paramVelocidade = leaderAd.velocidade !== undefined && leaderAd.velocidade !== null 
        ? Number(leaderAd.velocidade) 
        : props.velocidade;
        
      const paramEstatico = leaderAd.estatico !== undefined && leaderAd.estatico !== null 
        ? (leaderAd.estatico == 1 || leaderAd.estatico === true) 
        : props.estatico;

      // Proteção Matemática: Se velocidade for 0 ou negativa, trava o carrossel.
      configEstatico.value = paramEstatico || paramVelocidade <= 0;
      configVelocidade.value = paramVelocidade > 0 ? paramVelocidade : 0;
    }
    
  } catch (error) {
    if (!axios.isCancel(error)) {
      console.error(`[AdTech] Falha ao carregar zona: ${props.posicionamento}`);
      ads.value = [];
    }
  } finally {
    loading.value = false;
  }
};

const registrarClique = (ad) => {
  if (!ad || !ad.id) return;
  axios.post(`/api/v1/hub/parceiros/${ad.id}/clique`).catch(() => {});
};

onMounted(() => {
  fetchAds();
});

onBeforeUnmount(() => {
  if (abortController) {
    abortController.abort();
  }
});
</script>

<style scoped>
@keyframes scrollVerticalUp {
  0% { transform: translateY(0); }
  100% { transform: translateY(-50%); }
}

.animate-scroll-vertical-up {
  /* A duração é injetada estritamente pelo atributo style via DB Payload */
  animation-name: scrollVerticalUp;
  animation-timing-function: linear;
  animation-iteration-count: infinite;
  
  will-change: transform;
  transform: translateZ(0); 
}

@media (prefers-reduced-motion: reduce) {
  .animate-scroll-vertical-up {
    animation: none !important;
    overflow-y: auto !important;
    position: static !important;
  }
}
</style>