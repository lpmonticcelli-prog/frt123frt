<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-6 rounded-xl shadow-md border border-slate-700 flex flex-col md:flex-row justify-between items-center gap-4 text-white">
      <div>
        <h2 class="text-2xl font-black tracking-tight">Loja 123fretei</h2>
        <p class="text-slate-300 mt-1 text-sm font-medium">Equipamentos, EPIs e acessórios usando o seu saldo operacional.</p>
      </div>
      <div class="bg-slate-900 px-6 py-3 rounded-lg border border-slate-700 text-center shadow-inner">
        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block mb-1">Saldo Disponível</span>
        <span class="text-2xl font-black text-emerald-400">R$ 0,00</span>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-500"></div>
    </div>

    <div v-else class="animate-fade-in">
      <div v-if="produtos.length === 0" class="bg-white p-10 rounded-xl border border-slate-200 text-center shadow-sm">
        <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
        <h3 class="text-lg font-black text-slate-700">Nenhum produto disponível</h3>
        <p class="text-slate-500 text-sm mt-1">A nossa equipa está a negociar novos equipamentos para si.</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div v-for="produto in produtos" :key="produto.id" class="bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-lg transition group flex flex-col">
          
          <div class="h-48 bg-slate-50 relative overflow-hidden flex items-center justify-center border-b border-slate-100 p-4">
            <img v-if="produto.imagem_url" :src="produto.imagem_url" class="max-h-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-300">
            <div v-else class="text-slate-300 font-black text-xs uppercase tracking-widest">Sem Imagem</div>
            <div class="absolute top-2 left-2 bg-blue-600 text-white text-[9px] font-black px-2 py-1 rounded uppercase tracking-wider shadow">
              Destaque
            </div>
          </div>
          
          <div class="p-5 flex-1 flex flex-col justify-between">
            <div>
              <h3 class="font-black text-slate-800 leading-tight">{{ produto.nome }}</h3>
              <p class="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed">{{ produto.descricao }}</p>
            </div>
            
            <div class="mt-5 border-t border-slate-100 pt-4 flex justify-between items-end">
              <div>
                <span class="text-[10px] text-slate-400 font-bold block uppercase">Valor Unitário</span>
                <span class="font-black text-lg text-slate-900">{{ formatarMoeda(produto.valor_cobrado) }}</span>
              </div>
              <button @click="comprarProduto(produto)" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-black hover:bg-emerald-700 shadow-sm transition-colors active:scale-95">
                Comprar
              </button>
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

const produtosRaw = ref([]);
const loading = ref(true);

const produtos = computed(() => produtosRaw.value.filter(p => p.categoria === 'produto'));

const formatarMoeda = (valor) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);

const carregarLoja = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/hub/parceiros');
    produtosRaw.value = res.data;
  } catch (error) {
    console.error('Erro ao carregar produtos da loja:', error);
  } finally {
    loading.value = false;
  }
};

const comprarProduto = async (produto) => {
  try {
    // Registra métrica de interesse/clique antes de processar a compra
    await axios.post(`/api/v1/hub/parceiros/${produto.id}/clique`);
  } catch (e) { }

  alert(`Atenção: A integração de pagamento com saldo de frete para o item "${produto.nome}" será implementada na próxima fase de desenvolvimento.`);
  
  if(produto.link_url) {
      window.open(produto.link_url, '_blank');
  }
};

onMounted(() => {
  carregarLoja();
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>