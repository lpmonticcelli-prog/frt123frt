<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 z-50 transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
      
      <!-- Cabeçalho -->
      <div class="bg-red-50 p-4 border-b border-red-100 flex items-center justify-between">
        <h3 class="text-lg font-bold text-red-700">Ação Bloqueada</h3>
        <button @click="$emit('close')" class="text-red-400 hover:text-red-600 focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <!-- Corpo -->
      <div class="p-6">
        <!-- Texto Ajustado (Legalmente Seguro contra Venda Casada) -->
        <p class="text-sm text-slate-600 mb-5 leading-relaxed">
          Para garantir a sua proteção e o compliance da operação, é obrigatório possuir um Seguro de Acidentes Pessoais ativo para realizar viagens sem cobertura de Gerenciamento de Risco (GR).
        </p>
        
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mb-6 relative overflow-hidden">
          <div class="absolute top-0 right-0 bg-blue-500 text-white text-[10px] px-2 py-1 rounded-bl-lg font-bold uppercase tracking-wider">
            Solução Rápida
          </div>
          <h4 class="font-bold text-blue-900 mt-1">Proteção IZA (Plano Básico)</h4>
          <p class="text-sm text-blue-800 mt-1">
            Por <strong>R$ 34,90/mês</strong> você garante cobertura para Acidentes Pessoais e libera o transporte desta mercadoria imediatamente.
          </p>
          <ul class="text-xs text-blue-700 mt-2 space-y-1">
            <li>✓ Cobertura de R$ 30 mil (Morte/Invalidez)</li>
            <li>✓ Desconto automático no seu saldo</li>
          </ul>
        </div>
        
        <p v-if="error" class="text-red-500 text-sm mb-4 text-center">{{ error }}</p>

        <!-- Ações e Rotas de Fuga -->
        <div class="flex flex-col space-y-4">
          <div class="flex space-x-3">
            <button 
              @click="$emit('close')" 
              :disabled="isLoading"
              class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-bold hover:bg-slate-200 disabled:opacity-50 transition-colors focus:outline-none"
            >
              Cancelar
            </button>
            
            <button 
              @click="contratarERetentar" 
              :disabled="isLoading"
              class="flex-1 bg-[#035D29] text-white py-3 rounded-xl font-black hover:bg-[#023818] disabled:opacity-50 flex justify-center items-center transition-colors shadow-md focus:outline-none"
            >
              <span v-if="isLoading">Processando...</span>
              <span v-else>Contratar e Liberar</span>
            </button>
          </div>
          
          <div class="flex flex-col space-y-2 pt-2 border-t border-slate-100">
            <!-- Rota de Fuga 1: Upsell -->
            <button @click="irParaPlanosCompletos" class="text-xs font-bold text-[#035D29] hover:text-[#023818] underline text-center focus:outline-none">
              Ver planos completos (Com Telemedicina e Assistência)
            </button>
            
            <!-- Rota de Fuga 2: Evitar Venda Casada (CDC Art. 39) -->
            <button @click="enviarApolicePropria" class="text-xs font-bold text-slate-500 hover:text-slate-800 underline text-center focus:outline-none">
              Já possuo um seguro próprio (Enviar apólice para análise)
            </button>
          </div>
        </div>

      </div>
      
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
// Ajustado para caminho relativo blindando contra erro do Vite (alias @ pode falhar dependendo da config)
import { useSeguroIza } from '../composables/useSeguroIza';

const router = useRouter();

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  mensagemErro: {
    type: String,
    default: 'Você precisa de um seguro ativo para aceitar esta carga.'
  }
});

const emit = defineEmits(['close', 'success']);
const { isLoading, error, contratarPlano } = useSeguroIza();

const contratarERetentar = async () => {
  const sucesso = await contratarPlano('basico');
  if (sucesso) {
    emit('success'); 
  }
};

const irParaPlanosCompletos = () => {
  emit('close');
  router.push({ name: 'MotoristaSeguros' });
};

const enviarApolicePropria = () => {
  emit('close');
  // Redireciona o motorista para a área de Suporte/SAC onde o time de backoffice analisará o documento
  router.push({ name: 'MotoristaMeusChamados' });
};
</script>