<template>
  <div class="fixed inset-0 z-[999] overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

      <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle w-full max-w-lg">
        
        <!-- HEADER -->
        <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center">
          <h3 class="text-lg font-black text-rose-600 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Ação Bloqueada
          </h3>
          <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 focus:outline-none">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <!-- VISÃO 1: OFERTA DO SEGURO (Foco Principal) -->
        <div v-if="!mostrarIsencao" class="p-6 space-y-6">
          <p class="text-sm text-slate-600 font-medium leading-relaxed">
            Para garantir a sua proteção e o compliance da operação, é recomendado possuir um Seguro de Acidentes Pessoais ativo para realizar viagens sem cobertura de Gerenciamento de Risco (GR).
          </p>

          <!-- Card IZA -->
          <div class="border-2 border-blue-100 bg-blue-50/50 rounded-xl p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-blue-600 text-white text-[10px] font-black px-3 py-1 uppercase tracking-wider rounded-bl-lg">Solução Rápida</div>
            
            <h4 class="font-black text-slate-800 text-lg mb-2">Proteção IZA (Plano Básico)</h4>
            <p class="text-sm text-slate-700 mb-4">
              Por apenas <strong class="text-blue-700 text-base">R$ 34,90/mês</strong> (menos de R$ 1,20 ao dia), você garante cobertura financeira imediata para você e sua família.
            </p>
            
            <ul class="text-sm text-slate-600 space-y-2 mb-6 font-medium">
              <li class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Cobertura de R$ 30 mil (Morte/Invalidez)</li>
              <li class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Desconto automático no seu saldo</li>
            </ul>

            <div class="flex gap-3">
              <button @click="$emit('close')" class="flex-1 px-4 py-3 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
              <button @click="contratarSeguro" :disabled="loading" class="flex-[2] px-4 py-3 bg-emerald-700 text-white font-black rounded-lg hover:bg-emerald-800 shadow-md transition-colors flex justify-center items-center">
                 <svg v-if="loading" class="w-5 h-5 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Contratar e Liberar
              </button>
            </div>
          </div>

          <div class="flex flex-col items-center gap-3 text-sm font-bold mt-4">
            <a href="#" class="text-slate-500 hover:text-slate-800 underline decoration-slate-300">Ver planos completos (Com Telemedicina e Assistência)</a>
            <a href="#" class="text-slate-500 hover:text-slate-800 underline decoration-slate-300">Já possuo um seguro próprio (Enviar apólice para análise)</a>
            
            <!-- O BOTÃO DE RECUSA (Fricção) -->
            <button @click="mostrarIsencao = true" class="mt-4 text-xs text-rose-500 hover:text-rose-700 uppercase tracking-wider">
              Desejo seguir desprotegido e assumir os riscos
            </button>
          </div>
        </div>

        <!-- VISÃO 2: TERMO DE ISENÇÃO DE RESPONSABILIDADE (Blindagem Jurídica) -->
        <div v-else class="p-6 space-y-5 bg-rose-50/30">
          <div class="bg-rose-100 border border-rose-200 text-rose-800 p-4 rounded-xl text-sm font-bold text-center">
            ⚠️ Atenção: Você está optando por viajar sem proteção financeira.
          </div>

          <div class="prose prose-sm prose-slate text-slate-700 bg-white border border-slate-200 p-4 rounded-lg h-56 overflow-y-auto scrollbar-clinical">
            <p class="mb-3 font-black text-slate-900 text-center uppercase tracking-widest">TERMO DE RENÚNCIA E ASSUNÇÃO DE RISCO</p>
            <p class="mb-2"><strong>1.</strong> Declaro estar ciente de que a viagem não possui cobertura de Gerenciamento de Risco (GR) e opto, por minha livre e espontânea vontade, por <strong>NÃO</strong> contratar o Seguro de Acidentes Pessoais oferecido a custo acessível.</p>
            <p class="mb-2"><strong>2.</strong> Assumo integral, exclusiva e irrestritamente todos os riscos operacionais, físicos, patrimoniais, ambientais e de vida inerentes ao trajeto rodoviário, <strong>incluindo, mas não se limitando a: acidentes de trânsito, avarias mecânicas, roubo, furto, latrocínio, sequestro, extorsão, perda parcial ou total do veículo e da carga, bem como casos fortuitos, força maior e desastres naturais (enchentes, deslizamentos, tempestades, etc.).</strong></p>
            <p><strong>3.</strong> Isento de forma irrevogável, irretratável e definitiva a <strong>123 FRETEI TECNOLOGIA LTDA</strong> de qualquer responsabilidade civil, criminal, trabalhista ou indenizatória em caso de acidentes, invalidez, óbito ou prejuízos patrimoniais, eximindo-a de toda e qualquer prestação de assistência financeira a mim, aos meus dependentes legais, ou aos proprietários da carga e do veículo.</p>
          </div>

          <label class="flex items-start gap-3 cursor-pointer select-none bg-white p-3 rounded-xl border border-rose-200 shadow-sm">
            <input v-model="termoIsencaoAceito" type="checkbox" class="mt-1 w-5 h-5 text-rose-600 bg-white border-rose-300 rounded focus:ring-rose-600 focus:ring-2 cursor-pointer">
            <span class="font-black text-rose-900 text-sm leading-tight">Li, compreendi os riscos e aceito isentar a 123FRETEI de QUALQUER responsabilidade sobre minha vida, carga e veículo (incluindo roubos e desastres naturais).</span>
          </label>

          <div class="flex flex-col gap-3 pt-2">
            <!-- Botão de recuo (Salvação) -->
            <button @click="mostrarIsencao = false" class="w-full px-4 py-3 bg-blue-600 text-white font-black rounded-lg hover:bg-blue-700 transition-colors shadow-md">
              Voltar e Proteger minha família (R$ 34,90)
            </button>
            
            <!-- Botão de assumir risco (Só habilita se marcar o checkbox) -->
            <button @click="aceitarRiscoELiberar" :disabled="!termoIsencaoAceito || loading" class="w-full px-4 py-3 bg-transparent border-2 border-slate-300 text-slate-500 font-bold rounded-lg hover:bg-slate-100 hover:text-slate-800 transition-colors disabled:opacity-50">
              Assumir Riscos e Aceitar Frete
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['close', 'frete-liberado']);
const props = defineProps({ cargaId: Number });

const mostrarIsencao = ref(false);
const termoIsencaoAceito = ref(false);
const loading = ref(false);

// Fluxo 1: Motorista contrata o seguro (Caminho Feliz)
const contratarSeguro = async () => {
    loading.value = true;
    try {
        await axios.post('/api/v1/motorista/seguros/contratar', { plano: 'iza_basico' });
        // Segue fluxo normal de aceite do frete
        await axios.post(`/api/v1/motorista/cargas/${props.cargaId}/aceitar`);
        emit('frete-liberado');
    } catch (error) {
        alert('Erro ao processar. Tente novamente.');
    } finally {
        loading.value = false;
    }
};

// Fluxo 2: Motorista assina o termo de isenção e vai desprotegido
const aceitarRiscoELiberar = async () => {
    if (!termoIsencaoAceito.value) return;
    loading.value = true;
    try {
        // Envia o aceite do frete com a flag de isenção ativada para o backend registrar o Log
        await axios.post(`/api/v1/motorista/cargas/${props.cargaId}/aceitar`, {
            assumiu_risco_sem_seguro: true // O BACKEND DEVE SALVAR ISSO COMO PROVA JURÍDICA
        });
        emit('frete-liberado');
    } catch (error) {
        alert('Erro ao registrar aceite. Tente novamente.');
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.scrollbar-clinical::-webkit-scrollbar { width: 4px; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>