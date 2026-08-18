<template>
  <div class="animate-fade-in space-y-4 sm:space-y-6 max-w-4xl mx-auto pb-8">
    
    <!-- CARD DE ISENÇÃO VIP (ESTRATÉGIA DE RETENÇÃO) -->
    <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl shadow-sm p-1 flex items-center overflow-hidden">
       <div class="flex-1 bg-white/95 backdrop-blur-sm rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
         <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center shrink-0 border border-amber-200">
               <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v1m6 1h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
            </div>
            <div>
               <h3 class="font-black text-slate-900 text-base sm:text-lg tracking-tight">Membro Fundador 123Fretei</h3>
               <p class="text-sm font-medium text-slate-600 mt-0.5 leading-relaxed">Você foi selecionado(a) para usar a plataforma sem pagar a mensalidade de <span class="line-through text-rose-500 font-bold ml-1">R$ 59,90</span>.</p>
            </div>
         </div>
         <div class="w-full sm:w-auto text-center shrink-0 bg-amber-50 px-4 py-2.5 rounded-lg border border-amber-200">
            <span class="block text-[10px] font-black text-amber-800 uppercase tracking-widest">Sua Assinatura</span>
            <span class="block text-amber-600 font-black text-sm mt-0.5">100% GRÁTIS POR 6 MESES</span>
         </div>
       </div>
    </div>

    <!-- CARTEIRA VIRTUAL (MOEDAS 123) -->
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8 relative overflow-hidden border border-slate-200">
      <!-- Glow Laranja no Fundo -->
      <div class="absolute top-0 right-0 -mr-8 -mt-8 w-64 h-64 rounded-full bg-[#ff5500] opacity-10 blur-3xl pointer-events-none" aria-hidden="true"></div>
      
      <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        
        <div>
          <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 flex items-center">
            <svg class="w-4 h-4 mr-1.5 opacity-70 text-[#ff5500]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.64-2.25 1.64-1.74 0-2.1-.96-2.15-1.92H8.01c.06 1.77 1.25 3.01 2.89 3.33V19h2.38v-1.63c1.55-.33 2.82-1.3 2.82-2.91 0-2.02-1.55-2.85-3.79-3.32z"/></svg>
            Seu Saldo em Moedas 123
          </h2>
          
          <div v-if="loading" class="animate-pulse h-12 w-32 bg-slate-200 rounded mt-2"></div>
          <div v-else class="text-5xl sm:text-6xl font-black tracking-tight text-[#ff5500] tabular-nums mt-2">
            {{ saldoMoedas }} <span class="text-lg font-bold text-slate-400 tracking-widest uppercase">Moedas</span>
          </div>
          <p class="text-sm text-slate-500 font-medium mt-2 max-w-sm">Use suas moedas para dar lances exclusivos e garantir os melhores fretes da plataforma.</p>
        </div>
        
        <div class="w-full md:w-auto shrink-0">
          <button class="w-full md:w-auto bg-[#ff5500] hover:bg-[#e64d00] text-white font-bold px-8 py-4 rounded-xl shadow-md transition-colors focus:outline-none focus:ring-2 focus:ring-[#ff5500] focus:ring-offset-2 flex items-center justify-center text-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Comprar Mais Moedas
          </button>
        </div>

      </div>
    </div>

    <!-- EXTRATO DE TRANSAÇÕES (MOEDAS) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-5 py-5 sm:px-6 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
        <h3 class="text-lg font-black text-slate-900 tracking-tight">Histórico de Uso de Moedas</h3>
      </div>

      <div v-if="loading" class="p-12 flex flex-col items-center justify-center text-slate-500 font-bold space-y-3">
        <svg class="w-10 h-10 animate-spin text-[#ff5500]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="text-sm">Buscando o livro-razão de moedas...</span>
      </div>
      
      <div v-else-if="!transacoes || transacoes.length === 0" class="p-16 text-center flex flex-col items-center">
        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 shadow-inner border border-slate-100">
          <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <p class="text-slate-900 font-black text-lg tracking-tight">Extrato Vazio</p>
        <p class="text-slate-500 text-sm mt-1 max-w-sm">Suas compras de moedas e lances efetuados aparecerão aqui.</p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div v-for="t in transacoes" :key="t.id" class="px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-between hover:bg-slate-50 transition-colors group">
          <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
            
            <!-- Ícone baseado se gastou moeda (lance) ou se comprou moeda -->
            <div :class="t.tipo === 'credito' ? 'bg-orange-50 text-[#ff5500] border-orange-200' : 'bg-slate-100 text-slate-600 border-slate-200'" class="w-12 h-12 rounded-full flex items-center justify-center font-black border shrink-0 shadow-sm transition-transform group-hover:scale-105">
              <svg v-if="t.tipo === 'credito'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            
            <div class="min-w-0 flex-1">
              <p class="text-sm sm:text-base font-black text-slate-900 truncate leading-tight" :title="t.descricao">{{ t.descricao }}</p>
              <p class="text-[10px] sm:text-[11px] text-slate-400 font-bold uppercase tracking-widest mt-1">{{ new Date(t.created_at).toLocaleString('pt-BR') }}</p>
            </div>
          </div>
          
          <div :class="t.tipo === 'credito' ? 'text-[#ff5500]' : 'text-slate-600'" class="text-base sm:text-lg font-black text-right tabular-nums whitespace-nowrap pl-4 tracking-tight">
            {{ t.tipo === 'credito' ? '+' : '-' }} {{ t.valor }} <span class="text-[10px] uppercase font-bold tracking-widest ml-1">Moedas</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Foco exclusivo no Saldo de Moedas
const saldoMoedas = ref(150); // Valor inicial chumbado. Depois virá da API.
const transacoes = ref([]);
const loading = ref(true);

const carregarExtrato = async () => {
  loading.value = true;
  try {
    // Quando a API estiver pronta para moedas, descomente e adapte isso:
    /*
    const response = await axios.get('/api/v1/motorista/carteira/moedas/extrato');
    saldoMoedas.value = response.data?.saldo_moedas || 0;
    transacoes.value = response.data?.transacoes || [];
    */
    
    // Por enquanto, simulamos um extrato de moedas para você ver o layout:
    setTimeout(() => {
        transacoes.value = [
            { id: 1, descricao: 'Compra de Pacote Turbo', tipo: 'credito', valor: 200, created_at: new Date().toISOString() },
            { id: 2, descricao: 'Lance na Carga #1045 (Itatiba -> SP)', tipo: 'debito', valor: 10, created_at: new Date(Date.now() - 86400000).toISOString() },
            { id: 3, descricao: 'Bônus de Cadastro Inicial', tipo: 'credito', valor: 50, created_at: new Date(Date.now() - 172800000).toISOString() }
        ];
        loading.value = false;
    }, 800);
    
  } catch (error) {
    console.error('Erro ao carregar carteira de moedas', error);
    transacoes.value = [];
    loading.value = false;
  }
};

onMounted(carregarExtrato);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>