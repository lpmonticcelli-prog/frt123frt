<template>
  <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6 animate-fade-in pb-8">
    
    <!-- HEADER DO FAQ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Central de Ajuda (FAQ)</h2>
        <p class="text-sm text-slate-500 mt-1 font-medium">Encontre respostas rápidas para as dúvidas mais comuns da operação.</p>
      </div>
      <div class="hidden sm:flex items-center justify-center w-12 h-12 bg-emerald-50 rounded-full border border-emerald-100 shrink-0">
        <svg class="w-6 h-6 text-[#035D29]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      </div>
    </div>

    <!-- ESTADO: CARREGANDO -->
    <div v-if="loading" class="flex flex-col items-center justify-center p-12 text-slate-500 font-bold bg-white rounded-2xl border border-slate-200 shadow-sm">
      <svg class="w-10 h-10 animate-spin mb-4 text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
      <span class="text-sm">Buscando base de conhecimento...</span>
    </div>

    <!-- ESTADO: VAZIO -->
    <div v-else-if="!temDadosSeguros" class="p-16 text-center bg-white rounded-2xl border border-slate-200 shadow-sm">
      <div class="mx-auto w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5 border border-slate-100 shadow-inner">
        <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h3 class="text-lg font-black text-slate-900 tracking-tight">Nenhum artigo encontrado</h3>
      <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">A nossa equipe de suporte está atualizando a base de conhecimento no momento.</p>
    </div>

    <!-- LISTA DE FAQS -->
    <div v-else class="space-y-6">
      <!-- Proteção extra no v-for usando (faqsData || {}) -->
      <div v-for="(questions, category) in (faqsData || {})" :key="category" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        <!-- CABEÇALHO DA CATEGORIA -->
        <div class="bg-slate-900 px-5 py-4 border-b border-slate-200 flex items-center">
          <h3 class="text-xs font-black text-white uppercase tracking-widest">{{ category }}</h3>
        </div>

        <!-- PERGUNTAS E RESPOSTAS -->
        <div class="divide-y divide-slate-100">
          <div v-for="faq in questions" :key="faq.id" class="group">
            <button 
              @click="toggle(faq.id)" 
              class="w-full flex justify-between items-center text-left px-5 py-5 focus:outline-none hover:bg-slate-50 transition-colors"
            >
              <span class="text-sm font-bold text-slate-900 group-hover:text-[#035D29] transition-colors pr-4">{{ faq.question }}</span>
              <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 transition-colors" :class="openFaq === faq.id ? 'bg-emerald-50 border border-emerald-100' : 'bg-slate-50 border border-slate-200 group-hover:bg-emerald-50 group-hover:border-emerald-100'">
                 <svg 
                   class="w-4 h-4 transform transition-transform duration-200" 
                   :class="openFaq === faq.id ? 'rotate-180 text-[#035D29]' : 'text-slate-400 group-hover:text-[#035D29]'"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor"
                 >
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                 </svg>
              </div>
            </button>
            
            <!-- RESPOSTA (EXPANSÍVEL) -->
            <div 
              v-show="openFaq === faq.id" 
              class="px-5 pb-5 pt-1 animate-fade-in"
            >
              <div class="p-5 bg-slate-50 rounded-xl border border-slate-200 text-sm text-slate-700 font-medium leading-relaxed shadow-inner">
                {{ faq.answer }}
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

// Já iniciamos com um objeto vazio garantido
const faqsData = ref({});
const loading = ref(true);
const openFaq = ref(null);

// Lógica de proteção simplificada e segura
const temDadosSeguros = computed(() => {
  if (!faqsData.value) return false;
  return Object.keys(faqsData.value).length > 0;
});

const toggle = (id) => {
  openFaq.value = openFaq.value === id ? null : id;
};

const fetchFaqs = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/suporte/faqs');
    
    // Filtro atômico com blindagem máxima contra retornos inesperados
    if (res && res.data && typeof res.data.data === 'object' && !Array.isArray(res.data.data)) {
       faqsData.value = res.data.data;
    } else {
       faqsData.value = {};
    }

  } catch (error) {
    console.error('[FAQ] Erro ao carregar:', error);
    faqsData.value = {}; // Força objeto vazio em caso de erro na rede
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchFaqs();
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>