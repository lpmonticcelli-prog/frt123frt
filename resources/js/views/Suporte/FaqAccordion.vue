<template>
  <div class="space-y-8 max-w-4xl mx-auto py-8">
    <div class="text-center mb-10">
      <h2 class="text-3xl font-black text-slate-900 tracking-tight">Central de Ajuda</h2>
      <p class="text-sm text-slate-500 mt-2">Encontre respostas rápidas para as dúvidas mais comuns.</p>
    </div>

    <div v-if="loading" class="flex justify-center p-12">
      <div class="animate-pulse flex flex-col items-center">
        <div class="h-8 w-32 bg-slate-200 rounded mb-4"></div>
        <div class="h-4 w-64 bg-slate-100 rounded"></div>
      </div>
    </div>

    <div v-else-if="!temDadosSeguros" class="text-center p-12 bg-white rounded-xl border border-slate-200 shadow-sm">
      <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-slate-500 font-bold">Nenhum artigo encontrado no momento.</p>
      <p class="text-xs text-slate-400 mt-1">Nossa equipa está a atualizar a base de conhecimento.</p>
    </div>

    <div v-else class="space-y-6">
      <div v-for="(questions, category) in faqsData" :key="category" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-wider">{{ category }}</h3>
        </div>

        <div class="divide-y divide-slate-100">
          <div v-for="faq in questions" :key="faq.id" class="px-6 py-4">
            <button 
              @click="toggle(faq.id)" 
              class="w-full flex justify-between items-center text-left focus:outline-none group"
            >
              <span class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors pr-4">{{ faq.question }}</span>
              <svg 
                class="w-5 h-5 text-slate-400 transform transition-transform duration-200 shrink-0" 
                :class="{'rotate-180 text-blue-600': openFaq === faq.id}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            
            <div 
              v-show="openFaq === faq.id" 
              class="mt-4 text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-lg border border-slate-100"
            >
              {{ faq.answer }}
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

const faqsData = ref({});
const loading = ref(true);
const openFaq = ref(null);

// Lógica de proteção infalível (Substitui o Object.keys falho)
const temDadosSeguros = computed(() => {
  if (faqsData.value === null || faqsData.value === undefined) {
    return false;
  }
  if (typeof faqsData.value !== 'object') {
    return false;
  }
  // Se for um array ou objeto válido, iteramos de forma segura
  for (let key in faqsData.value) {
    if (Object.prototype.hasOwnProperty.call(faqsData.value, key)) {
      return true;
    }
  }
  return false;
});

const toggle = (id) => {
  openFaq.value = openFaq.value === id ? null : id;
};

const fetchFaqs = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/suporte/faqs');
    
    // Filtro atômico contra lixo vindo da API
    if (res && res.data && res.data.data) {
       faqsData.value = res.data.data;
    } else {
       faqsData.value = {};
    }

  } catch (error) {
    console.error('[FAQ] Erro ao carregar:', error);
    faqsData.value = {};
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchFaqs();
});
</script>