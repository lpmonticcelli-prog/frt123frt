<template>
  <div class="max-w-4xl mx-auto py-8">
    <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Central de Ajuda (FAQ)</h2>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <div v-else-if="Object.keys(faqs)?.length === 0" class="text-center text-gray-500 py-12">
      Nenhuma documentação disponível no momento.
    </div>

    <div v-else class="space-y-6">
      <div v-for="(questions, category) in faqs" :key="category" class="bg-white rounded-lg shadow-sm border border-gray-200">
        
        <div class="bg-slate-50 px-6 py-4 border-b border-gray-200 rounded-t-lg">
          <h3 class="text-lg font-bold text-slate-800 uppercase tracking-wide">
            {{ formatCategoryName(category) }}
          </h3>
        </div>

        <ul class="divide-y divide-gray-100">
          <li v-for="faq in questions" :key="faq.id" class="px-6 py-4">
            <button 
              @click="toggle(faq.id)" 
              class="w-full flex justify-between items-center text-left focus:outline-none"
            >
              <span class="font-medium text-slate-700">{{ faq.question }}</span>
              <svg 
                class="ml-4 h-5 w-5 text-slate-400 transition-transform duration-200" 
                :class="{ 'transform rotate-180': activeId === faq.id }"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            
            <transition 
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <div v-show="activeId === faq.id" class="mt-4 text-slate-600 text-sm leading-relaxed whitespace-pre-wrap">
                {{ faq.answer }}
              </div>
            </transition>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const faqs = ref({});
const loading = ref(true);
const activeId = ref(null);

// Mapeamento Dicionário de Negócio
const categoryLabels = {
  parceiros: 'Parceiros Estratégicos',
  loja: 'Loja 123fretei',
  voucher: 'Gestão de Vouchers',
  recebimento: 'Recebimentos e Pagamentos',
  gr: 'Gerenciamento de Risco (GR)',
  emissao_nf: 'Emissão de NF-e / CT-e'
};

const formatCategoryName = (key) => {
  return categoryLabels[key] || key?.replace('_', ' ');
};

const toggle = (id) => {
  activeId.value = activeId.value === id ? null : id;
};

const fetchFaqs = async () => {
  try {
    // Aponta para a nova API V1 padronizada
    const response = await axios.get('/api/v1/suporte/faqs');
    faqs.value = response.data.data;
  } catch (error) {
    console.error('[FAQ] Falha ao recuperar base de conhecimento.', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchFaqs();
});
</script>