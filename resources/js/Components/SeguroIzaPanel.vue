<template>
  <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-200">
    <div class="mb-6 border-b border-slate-100 pb-4">
      <h2 class="text-2xl font-black text-slate-900 tracking-tight">Proteção IZA - Clube de Benefícios</h2>
      <p class="text-slate-500 font-medium text-sm mt-1">Gerencie sua cobertura de Acidentes Pessoais e acesse os serviços de Telemedicina e Assistência.</p>
    </div>
    
    <div v-if="isLoading" class="p-12 text-center text-slate-500 font-medium flex flex-col items-center">
      <svg class="w-10 h-10 animate-spin mb-4 text-[#035D29]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
      Sincronizando com a Seguradora...
    </div>

    <!-- CONTEÚDO CARREGADO -->
    <div v-else>
      <!-- SE O MOTORISTA JÁ TIVER SEGURO (Renderiza no topo) -->
      <div v-if="seguroStatus?.possui_seguro" class="bg-emerald-50 border border-emerald-200 p-6 rounded-2xl shadow-inner mb-8">
        <div class="flex items-center gap-3 mb-4">
          <div class="bg-emerald-100 p-2 rounded-full text-emerald-600">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
          </div>
          <div>
            <h3 class="text-emerald-900 font-black text-xl">Proteção Ativa</h3>
            <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Status: {{ seguroStatus.status }}</span>
          </div>
        </div>
        
        <div class="bg-white rounded-xl p-4 border border-emerald-100 mb-4 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
          <div>
            <p class="text-slate-700 font-medium">Plano contratado: <strong class="text-slate-900 uppercase">{{ seguroStatus.plano }}</strong></p>
            <p v-if="seguroStatus.is_ativo" class="text-slate-700 font-medium mt-1">Vigência até: <strong class="text-slate-900">{{ formatarData(seguroStatus.vigencia_fim) }}</strong></p>
          </div>
          
          <!-- Botão de Cancelamento Injetado -->
          <button @click="cancelarSeguro" :disabled="isLoading" class="text-sm font-bold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 border border-red-100 transition-colors px-4 py-2 rounded-lg disabled:opacity-50 flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Cancelar Proteção
          </button>
        </div>

        <p v-if="seguroStatus.status === 'pendente_emissao'" class="text-amber-700 bg-amber-50 p-3 rounded-lg border border-amber-200 text-sm font-medium mt-4">
          ⏳ Sua apólice está sendo emitida pela seguradora. Esse processo pode levar até 24h, mas <strong>seus fretes já estão liberados!</strong>
        </p>
      </div>

      <!-- MURAL DE VENDAS (Agora renderiza sempre abaixo do status) -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Cenário 1: Básico -->
        <div class="border border-slate-200 rounded-2xl p-6 bg-slate-50 hover:bg-white hover:shadow-lg transition-all flex flex-col">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-1">Básico</h3>
          <p class="text-slate-500 text-sm font-medium mb-4 h-10">O essencial para liberar suas cargas com tranquilidade.</p>
          <p class="text-4xl font-black text-[#035D29] tracking-tighter mb-6">R$ 34,90<span class="text-sm font-bold text-slate-400">/mês</span></p>
          <ul class="text-sm text-slate-700 space-y-3 mb-8 flex-1 font-medium">
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Morte Acidental (R$ 30.000)</li>
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Invalidez Acidental (R$ 30.000)</li>
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Despesas Médicas (R$ 5.000)</li>
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Assistência Residencial Básica</li>
          </ul>
          <button @click="assinar('basico')" :disabled="isLoading" class="w-full bg-slate-900 text-white py-3 rounded-xl font-black hover:bg-slate-800 transition-colors disabled:opacity-50 mt-auto">
            Contratar Básico
          </button>
        </div>

        <!-- Cenário 2: Intermediário -->
        <div class="border-2 border-[#035D29] rounded-2xl p-6 bg-white shadow-xl transform lg:-translate-y-2 relative flex flex-col">
          <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#035D29] text-white text-[10px] px-3 py-1 rounded-full font-black uppercase tracking-widest shadow-sm">
            Mais Escolhido
          </div>
          <h3 class="text-lg font-black text-[#035D29] uppercase tracking-tight mb-1">Intermediário</h3>
          <p class="text-slate-500 text-sm font-medium mb-4 h-10">Maior cobertura para você e cuidados com a saúde.</p>
          <p class="text-4xl font-black text-[#035D29] tracking-tighter mb-6">R$ 59,90<span class="text-sm font-bold text-slate-400">/mês</span></p>
          <ul class="text-sm text-slate-700 space-y-3 mb-8 flex-1 font-medium">
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Morte Acidental (R$ 50.000)</li>
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Invalidez Acidental (R$ 50.000)</li>
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Assistência Residencial Intermediária</li>
            <li class="flex items-start gap-2 font-bold text-slate-900"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Telemedicina & Desconto Farmácia</li>
          </ul>
          <button @click="assinar('intermediario')" :disabled="isLoading" class="w-full bg-[#035D29] text-white py-3 rounded-xl font-black hover:bg-[#023818] transition-colors shadow-md disabled:opacity-50 mt-auto">
            Contratar Intermediário
          </button>
        </div>

        <!-- Cenário 3: Superior -->
        <div class="border border-slate-200 rounded-2xl p-6 bg-slate-50 hover:bg-white hover:shadow-lg transition-all flex flex-col">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-1">Superior</h3>
          <p class="text-slate-500 text-sm font-medium mb-4 h-10">Proteção máxima extensiva para sua família.</p>
          <p class="text-4xl font-black text-[#035D29] tracking-tighter mb-6">R$ 99,90<span class="text-sm font-bold text-slate-400">/mês</span></p>
          <ul class="text-sm text-slate-700 space-y-3 mb-8 flex-1 font-medium">
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Morte Acidental (R$ 80.000)</li>
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Invalidez Acidental (R$ 80.000)</li>
            <li class="flex items-start gap-2"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Assistência Residencial Superior</li>
            <li class="flex items-start gap-2 font-bold text-slate-900"><svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Telemedicina Familiar</li>
          </ul>
          <button @click="assinar('superior')" :disabled="isLoading" class="w-full bg-slate-900 text-white py-3 rounded-xl font-black hover:bg-slate-800 transition-colors disabled:opacity-50 mt-auto">
            Contratar Superior
          </button>
        </div>

      </div>
      
      <div v-if="error" class="mt-6 bg-red-50 p-4 rounded-xl border border-red-200 flex items-center gap-3">
         <svg class="w-6 h-6 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
         <p class="text-sm font-bold text-red-800">{{ error }}</p>
      </div>

      <!-- Botão Transparência -->
      <div class="mt-8 text-center border-t border-slate-100 pt-6">
         <button @click="modalDetalhesAberto = true" class="text-sm font-bold text-[#035D29] hover:text-[#023818] underline focus:outline-none flex items-center justify-center gap-2 mx-auto">
           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
           Ver Condições Gerais, Carências e Limites Completos
         </button>
      </div>
    </div>

    <!-- Modal de Detalhes -->
    <IzaDetalhesModal :isOpen="modalDetalhesAberto" @close="modalDetalhesAberto = false" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useSeguroIza } from '../composables/useSeguroIza';
import IzaDetalhesModal from './IzaDetalhesModal.vue';
import axios from 'axios';

const modalDetalhesAberto = ref(false);

const { seguroStatus, isLoading, error, carregarStatus, contratarPlano } = useSeguroIza();

onMounted(() => {
    carregarStatus();
});

const assinar = async (plano) => {
    if(confirm(`Tem certeza que deseja contratar o plano ${plano.toUpperCase()}?\n\nO valor será descontado automaticamente dos seus fretes pela 123FRETEI.`)) {
        await contratarPlano(plano);
    }
};

const cancelarSeguro = async () => {
    if(confirm('Tem certeza que deseja cancelar sua Proteção Ativa?\n\nVocê perderá todas as coberturas de acidentes e benefícios imediatamente.')) {
        try {
            isLoading.value = true;
            const response = await axios.post('/api/v1/motorista/seguros/cancelar');
            
            // CORREÇÃO AQUI: Mutação manual do estado do Vue para sumir com o card verde na mesma hora
            if (seguroStatus.value) {
                seguroStatus.value.possui_seguro = false;
                seguroStatus.value.status = 'cancelado';
                seguroStatus.value.is_ativo = false;
                seguroStatus.value.plano = null;
            }

            alert(response.data.message || 'Seguro cancelado com sucesso.');
            await carregarStatus(); 
        } catch (err) {
            alert(err.response?.data?.error || 'Erro ao cancelar o seguro. Tente novamente.');
        } finally {
            isLoading.value = false;
        }
    }
};

const formatarData = (data) => {
  if (!data) return '';
  return new Date(data).toLocaleDateString('pt-BR');
};
</script>