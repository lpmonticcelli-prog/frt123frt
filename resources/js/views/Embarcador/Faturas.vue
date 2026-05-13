<template>
  <div class="space-y-6 relative">
    
    <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Gestão Financeira & Faturamento</h2>
        <p class="text-sm text-slate-500 mt-1">Gira as faturas consolidadas e repasses da plataforma.</p>
      </div>
      <button @click="fetchFaturas" :disabled="loading" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors disabled:opacity-50 flex items-center shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        {{ loading ? 'Sincronizando...' : 'Atualizar Faturas' }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
        <span class="text-xs font-black text-slate-400 uppercase tracking-wider mb-1">Total em Aberto</span>
        <span class="text-3xl font-black text-slate-900">{{ formatarMoeda(resumo.totalAberto) }}</span>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
        <span class="text-xs font-black text-slate-400 uppercase tracking-wider mb-1">Faturas Pendentes</span>
        <span class="text-3xl font-black text-orange-600">{{ resumo.qtdAbertas }}</span>
      </div>
      <div class="bg-slate-900 p-6 rounded-xl border border-slate-800 shadow-sm flex flex-col justify-center">
        <span class="text-xs font-black text-slate-400 uppercase tracking-wider mb-1">Total Já Pago</span>
        <span class="text-3xl font-black text-green-400">{{ formatarMoeda(resumo.totalPago) }}</span>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div v-if="loading && (!faturas || faturas.length === 0)" class="p-12 text-center text-slate-500 font-medium text-sm">
        A carregar dados financeiros...
      </div>

      <div v-else-if="!faturas || faturas.length === 0" class="p-16 text-center">
        <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
          <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <h3 class="text-base font-bold text-slate-900">Nenhuma fatura gerada</h3>
        <p class="text-sm text-slate-500 mt-1">O motor de faturamento ainda não fechou nenhum ciclo para a sua conta.</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200 text-left">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Referência</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Período / Emissão</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Vencimento</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Valor Total</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100" :class="{ 'opacity-50 pointer-events-none': loading }">
          <tr v-for="fatura in faturas" :key="fatura.id" class="hover:bg-slate-50/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-black text-slate-900 font-mono">Comp. {{ fatura.mes_referencia }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-slate-700">{{ formatarData(fatura.created_at) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div :class="['text-sm font-bold', isVencida(fatura) ? 'text-red-600' : 'text-slate-900']">
                {{ formatarData(fatura.data_vencimento) }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-slate-900">
              {{ formatarMoeda(fatura.valor_total) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="['px-3 py-1 inline-flex text-[10px] font-black uppercase tracking-wider rounded-md border', getStatusClass(fatura)]">
                {{ fatura.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right space-x-3">
              <button @click="abrirDetalhes(fatura)" class="text-slate-600 hover:text-slate-900 font-bold transition-colors text-sm">
                Detalhes
              </button>
              <button 
                v-if="fatura.status === 'pendente'" 
                @click="abrirPagamento(fatura)" 
                class="inline-flex items-center px-4 py-1.5 bg-slate-900 text-white text-xs font-bold rounded shadow-sm hover:bg-slate-800 transition-colors"
              >
                Pagar Agora
              </button>
              <a 
                v-if="fatura.status === 'paga'" 
                :href="fatura.link_boleto || '#'" 
                target="_blank"
                class="inline-flex items-center px-3 py-1.5 bg-green-50 border border-green-200 text-green-700 font-bold text-xs rounded hover:bg-green-100 transition-colors"
              >
                Baixar Boleto/Recibo
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModalDetalhes" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="fecharModalDetalhes"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-slate-200">
          <div class="bg-white px-6 pt-6 pb-6 sm:p-8">
            <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-6">
              <div>
                <h3 class="text-xl font-black text-slate-900 uppercase">Composição da Fatura</h3>
                <p class="text-sm text-slate-500 font-mono mt-1">Competência: {{ faturaSelecionada?.mes_referencia }}</p>
              </div>
              <span :class="['px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded border', getStatusClass(faturaSelecionada)]">
                {{ faturaSelecionada?.status }}
              </span>
            </div>
            
            <div class="bg-slate-50 p-5 rounded-lg border border-slate-200 mb-6">
              <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Resumo de Custos (Taxa 123fretei)</h4>
              <div class="flex justify-between text-sm font-bold text-slate-700 mb-2">
                <span>Mensalidade Fixa SaaS</span>
                <span>{{ formatarMoeda(faturaSelecionada?.detalhes_cargas?.resumo?.mensalidade_fixa || 0) }}</span>
              </div>
              <div class="flex justify-between text-sm font-bold text-slate-700 mb-4 border-b border-slate-200 pb-4">
                <span>Total Taxas Variáveis (Comissões de Carga)</span>
                <span>{{ formatarMoeda(faturaSelecionada?.detalhes_cargas?.resumo?.total_taxas_variaveis || 0) }}</span>
              </div>
              <div class="flex justify-between text-lg font-black text-slate-900">
                <span>TOTAL A PAGAR</span>
                <span>{{ formatarMoeda(faturaSelecionada?.valor_total) }}</span>
              </div>
            </div>

            <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Relação de Cargas Entregues no Mês</h4>
            <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-lg">
              <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 sticky top-0">
                  <tr>
                    <th class="px-4 py-2 font-bold text-slate-600">Carga ID</th>
                    <th class="px-4 py-2 font-bold text-slate-600">Rota</th>
                    <th class="px-4 py-2 font-bold text-slate-600 text-right">Taxa Cobrada</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="carga in faturaSelecionada?.detalhes_cargas?.cargas" :key="carga.id" class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono font-bold text-slate-800">#{{ carga.id }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ carga.rota }}</td>
                    <td class="px-4 py-3 text-right font-medium text-orange-600">{{ formatarMoeda(carga.taxa_cobrada) }}</td>
                  </tr>
                  <tr v-if="!faturaSelecionada?.detalhes_cargas?.cargas || faturaSelecionada.detalhes_cargas.cargas.length === 0">
                    <td colspan="3" class="px-4 py-6 text-center text-slate-400 italic">Esta fatura não possui cargas variáveis vinculadas.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse border-t border-slate-200">
            <button @click="fecharModalDetalhes" class="px-6 py-2 bg-white border border-slate-300 rounded-lg text-sm font-bold text-slate-700 hover:bg-slate-100 transition-colors">
              Fechar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModalPagamento" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-80 transition-opacity backdrop-blur-sm" @click="fecharModalPagamento"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-slate-200">
          <div class="bg-white px-6 pt-6 pb-8 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
              <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-xl font-black text-slate-900 mb-1">Pagamento via PIX</h3>
            <p class="text-sm text-slate-500 mb-6">Competência {{ faturaSelecionada?.mes_referencia }} • Valor: <strong class="text-slate-900">{{ formatarMoeda(faturaSelecionada?.valor_total) }}</strong></p>
            
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-6">
              <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="QR Code PIX Mock" class="w-40 h-40 mx-auto mb-4 opacity-80 mix-blend-multiply" />
              <p class="text-xs font-bold text-slate-500 uppercase mb-2">Pix Copia e Cola</p>
              <div class="flex items-center bg-white border border-slate-300 rounded p-2">
                <input type="text" readonly value="00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-426655440000" class="flex-1 text-xs font-mono bg-transparent outline-none text-slate-600" id="pixCode" />
                <button @click="copiarPix" class="ml-2 text-blue-600 font-bold text-xs hover:text-blue-800 uppercase">Copiar</button>
              </div>
            </div>

            <p class="text-xs text-slate-500 italic mb-6">
              A baixa desta fatura ocorre em até 5 minutos após o pagamento.
            </p>

            <button @click="simularPagamentoAprovado" :disabled="simulandoPagamento" class="w-full bg-slate-900 text-white font-black py-3 rounded-lg hover:bg-slate-800 transition-colors disabled:opacity-50">
              {{ simulandoPagamento ? 'Processando Baixa...' : '[DEV] Simular Pagamento Aprovado' }}
            </button>
          </div>
          <div class="bg-slate-50 px-6 py-4 flex border-t border-slate-200">
            <button @click="fecharModalPagamento" class="w-full text-center text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors">
              Cancelar e Voltar
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const faturas = ref([]);
const loading = ref(true);

const showModalDetalhes = ref(false);
const showModalPagamento = ref(false);
const faturaSelecionada = ref(null);
const simulandoPagamento = ref(false);

const resumo = computed(() => {
  let totalAberto = 0;
  let qtdAbertas = 0;
  let totalPago = 0;

  faturas.value.forEach(f => {
    if (f.status === 'pendente' || f.status === 'vencida') {
      totalAberto += parseFloat(f.valor_total || 0);
      qtdAbertas++;
    } else if (f.status === 'paga') {
      totalPago += parseFloat(f.valor_total || 0);
    }
  });

  return { totalAberto, qtdAbertas, totalPago };
});

const formatarMoeda = (valor) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);
};

const formatarData = (dataString) => {
  if (!dataString) return '--';
  const d = new Date(dataString);
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

const isVencida = (fatura) => {
  if (fatura.status === 'paga') return false;
  const hoje = new Date();
  const vencimento = new Date(fatura.data_vencimento);
  return vencimento < hoje;
};

const getStatusClass = (fatura) => {
  if (fatura.status === 'paga') return 'bg-green-50 text-green-700 border-green-200';
  if (isVencida(fatura)) return 'bg-red-50 text-red-700 border-red-200';
  return 'bg-amber-50 text-amber-700 border-amber-200';
};

const fetchFaturas = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/embarcador/faturas');
    faturas.value = response.data.data || response.data;
  } catch (error) {
    console.error('Erro ao buscar faturas:', error);
  } finally {
    loading.value = false;
  }
};

const abrirDetalhes = async (fatura) => {
  faturaSelecionada.value = fatura;
  showModalDetalhes.value = true;
};

const fecharModalDetalhes = () => {
  showModalDetalhes.value = false;
  faturaSelecionada.value = null;
};

const abrirPagamento = (fatura) => {
  faturaSelecionada.value = fatura;
  showModalPagamento.value = true;
};

const fecharModalPagamento = () => {
  if (simulandoPagamento.value) return;
  showModalPagamento.value = false;
  faturaSelecionada.value = null;
};

const copiarPix = () => {
  const pixInput = document.getElementById('pixCode');
  pixInput.select();
  document.execCommand('copy');
  alert('Código PIX Copiado para a área de transferência!');
};

// Simulador de baixa para desenvolvimento
const simularPagamentoAprovado = async () => {
  simulandoPagamento.value = true;
  try {
    // Simula tempo de processamento
    await new Promise(r => setTimeout(r, 1500));
    
    // Altera o status no frontend 
    // NOTA: Na vida real, o backend dispararia o Webhook de pagamento aprovado
    const index = faturas.value.findIndex(f => f.id === faturaSelecionada.value.id);
    if (index !== -1) {
      faturas.value[index].status = 'paga';
    }
    
    alert('✅ Pagamento Confirmado com Sucesso! Recibo em processo de emissão.');
    fecharModalPagamento();
  } catch (e) {
    alert('Erro ao processar.');
  } finally {
    simulandoPagamento.value = false;
  }
};

onMounted(() => {
  fetchFaturas();
});
</script>