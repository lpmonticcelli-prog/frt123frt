<template>
  <div class="space-y-6 relative">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-5 rounded-xl border border-surface-200 shadow-clinical-sm gap-4">
      <div>
        <h2 class="text-xl font-bold text-surface-900 tracking-tight">Gestão Financeira & Faturamento</h2>
        <p class="text-sm text-surface-500 mt-1">Gira as faturas consolidadas e repasses da plataforma.</p>
      </div>
      <button @click="fetchFaturas" :disabled="loading" class="w-full sm:w-auto px-4 py-2 border border-surface-300 rounded-lg text-sm font-bold text-surface-700 bg-white hover:bg-surface-50 transition-colors disabled:opacity-50 flex items-center justify-center shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-surface-500">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        {{ loading ? 'Sincronizando Malha...' : 'Atualizar Faturas' }}
      </button>
    </div>

    <div class="bg-transparent lg:bg-white rounded-xl lg:shadow-clinical-sm lg:border border-surface-200 overflow-hidden">
      
      <div v-if="loading && faturas.length === 0" class="p-12 text-center text-surface-500 font-medium text-sm bg-white rounded-xl shadow-sm border border-surface-200 lg:border-none lg:shadow-none">
        <svg class="w-8 h-8 animate-spin text-brand-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        A consultar motor de faturamento...
      </div>

      <div v-else-if="faturas.length === 0" class="p-16 text-center bg-white rounded-xl shadow-sm border border-surface-200 lg:border-none lg:shadow-none">
        <div class="mx-auto w-16 h-16 bg-surface-50 rounded-full flex items-center justify-center mb-4 border border-surface-100">
          <svg class="w-8 h-8 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <h3 class="text-base font-bold text-surface-900">Nenhuma fatura gerada</h3>
        <p class="text-sm text-surface-500 mt-1">Sua infraestrutura logística ainda não gerou cobranças no ciclo atual.</p>
      </div>

      <div v-else class="w-full">
        <table class="min-w-full text-left border-collapse block lg:table">
          <thead class="bg-surface-50 hidden lg:table-header-group">
            <tr>
              <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Referência</th>
              <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Emissão</th>
              <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Vencimento</th>
              <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Valor Total</th>
              <th scope="col" class="px-6 py-4 text-xs font-bold text-surface-500 uppercase tracking-wider">Status</th>
              <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-surface-500 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="block lg:table-row-group divide-y-0 lg:divide-y divide-surface-100" :class="{ 'opacity-50 pointer-events-none': loading }">
            
            <tr v-for="fatura in faturas" :key="fatura.id" class="block lg:table-row bg-white hover:bg-surface-50/50 transition-colors mb-4 lg:mb-0 rounded-xl lg:rounded-none shadow-clinical-sm lg:shadow-none border border-surface-200 lg:border-none overflow-hidden">
              
              <td class="block lg:table-cell px-4 py-3 lg:px-6 lg:py-4 border-b border-surface-100 lg:border-none">
                <div class="lg:hidden text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1">Referência</div>
                <div class="text-sm font-black text-surface-900 font-mono">Comp. {{ fatura.mes_referencia }}</div>
              </td>
              
              <td class="block lg:table-cell px-4 py-3 lg:px-6 lg:py-4 border-b border-surface-100 lg:border-none">
                <div class="lg:hidden text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1">Emissão</div>
                <div class="text-sm font-bold text-surface-700">{{ formatarData(fatura.created_at) }}</div>
              </td>
              
              <td class="block lg:table-cell px-4 py-3 lg:px-6 lg:py-4 border-b border-surface-100 lg:border-none">
                <div class="flex lg:block justify-between items-center">
                  <div class="lg:hidden text-[10px] font-black text-surface-400 uppercase tracking-widest">Vencimento</div>
                  <div :class="['text-sm font-bold', isVencida(fatura) ? 'text-rose-600' : 'text-surface-900']">
                    {{ formatarData(fatura.data_vencimento) }}
                  </div>
                </div>
              </td>
              
              <td class="block lg:table-cell px-4 py-3 lg:px-6 lg:py-4 border-b border-surface-100 lg:border-none">
                <div class="flex lg:block justify-between items-center">
                  <div class="lg:hidden text-[10px] font-black text-surface-400 uppercase tracking-widest">Valor Total</div>
                  <div class="text-sm font-black text-surface-900 tabular-nums">{{ formatarMoeda(fatura.valor_total) }}</div>
                </div>
              </td>
              
              <td class="block lg:table-cell px-4 py-3 lg:px-6 lg:py-4 border-b border-surface-100 lg:border-none">
                <div class="flex lg:block justify-between items-center">
                  <div class="lg:hidden text-[10px] font-black text-surface-400 uppercase tracking-widest">Status</div>
                  <span :class="['px-3 py-1.5 lg:py-1 inline-flex text-[10px] font-black uppercase tracking-wider rounded-md border', getStatusClass(fatura)]">
                    {{ fatura.status }}
                  </span>
                </div>
              </td>
              
              <td class="block lg:table-cell px-4 py-4 lg:px-6 lg:py-4 bg-surface-50 lg:bg-transparent rounded-b-xl lg:rounded-none">
                <div class="flex flex-wrap lg:justify-end gap-2 lg:gap-3 items-center w-full">
                  <button @click="abrirDetalhes(fatura.id)" class="flex-1 lg:flex-none text-center bg-white lg:bg-transparent border lg:border-none border-surface-200 px-4 py-2 lg:px-2 lg:py-1 rounded-lg lg:rounded text-surface-600 hover:text-brand-800 font-bold transition-colors text-xs lg:text-[10px] uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-brand-500">
                    Detalhes
                  </button>
                  <button v-if="fatura.status === 'pendente' || fatura.status === 'vencida'" @click="abrirPagamento(fatura)" class="w-full lg:w-auto inline-flex justify-center items-center px-4 py-2.5 lg:py-1.5 bg-surface-900 text-white font-bold text-xs rounded-lg lg:rounded hover:bg-surface-800 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-surface-500">
                    Pagar Agora
                  </button>
                  <a v-if="fatura.status === 'paga' && fatura.link_boleto" :href="fatura.link_boleto" target="_blank" class="w-full lg:w-auto inline-flex justify-center items-center px-4 py-2.5 lg:py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs rounded-lg lg:rounded hover:bg-emerald-100 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    Baixar Recibo
                  </a>
                </div>
              </td>

            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showModalDetalhes" class="fixed inset-0 z-modal overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-surface-950/80 transition-opacity backdrop-blur-sm" @click="fecharModalDetalhes"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-surface-200">
          <div class="bg-white px-6 pt-6 pb-6 sm:p-8">
            <div class="flex justify-between items-start border-b border-surface-100 pb-4 mb-6">
              <div>
                <h3 class="text-xl font-black text-surface-900 uppercase tracking-tight">Composição da Fatura</h3>
                <p class="text-sm text-surface-500 font-mono mt-1">Competência: {{ faturaSelecionada?.mes_referencia || 'Carregando...' }}</p>
              </div>
              <span v-if="faturaSelecionada" :class="['px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded border', getStatusClass(faturaSelecionada)]">
                {{ faturaSelecionada.status }}
              </span>
            </div>
            
            <div v-if="carregandoDetalhes" class="py-12 text-center text-surface-500 font-medium animate-pulse">
              Descompactando logs e composição de cargas associadas...
            </div>
            
            <div v-else-if="faturaSelecionada">
               <div class="bg-surface-50 p-5 rounded-lg border border-surface-200 mb-6">
                <h4 class="text-xs font-black text-surface-400 uppercase tracking-wider mb-3">Resumo de Custos (Taxa 123fretei)</h4>
                <div class="flex justify-between text-sm font-bold text-surface-700 mb-2">
                  <span>Mensalidade Fixa SaaS</span>
                  <span>{{ formatarMoeda(faturaSelecionada.detalhes_cargas?.resumo?.mensalidade_fixa || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-surface-700 mb-4 border-b border-surface-200 pb-4">
                  <span>Total Taxas Variáveis (Comissões de Carga)</span>
                  <span>{{ formatarMoeda(faturaSelecionada.detalhes_cargas?.resumo?.total_taxas_variaveis || 0) }}</span>
                </div>
                <div class="flex justify-between text-lg font-black text-surface-900">
                  <span>TOTAL A PAGAR</span>
                  <span>{{ formatarMoeda(faturaSelecionada.valor_total) }}</span>
                </div>
              </div>

              <h4 class="text-xs font-black text-surface-400 uppercase tracking-wider mb-3">Relação de Cargas Entregues no Mês</h4>
              <div class="max-h-64 overflow-y-auto border border-surface-200 rounded-lg scrollbar-clinical">
                <table class="min-w-full divide-y divide-surface-100 text-sm text-left">
                  <thead class="bg-surface-50 sticky top-0">
                    <tr>
                      <th class="px-4 py-2 font-bold text-surface-600">Carga ID</th>
                      <th class="px-4 py-2 font-bold text-surface-600">Rota</th>
                      <th class="px-4 py-2 font-bold text-surface-600 text-right">Taxa Cobrada</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-surface-100 bg-white">
                    <tr v-for="carga in faturaSelecionada.detalhes_cargas?.cargas || []" :key="carga.id" class="hover:bg-surface-50">
                      <td class="px-4 py-3 font-mono font-bold text-surface-800">#{{ carga.id }}</td>
                      <td class="px-4 py-3 text-surface-600">{{ carga.rota || 'Rota Desconhecida' }}</td>
                      <td class="px-4 py-3 text-right font-bold text-surface-900">{{ formatarMoeda(carga.taxa_cobrada) }}</td>
                    </tr>
                    <tr v-if="!faturaSelecionada.detalhes_cargas?.cargas || faturaSelecionada.detalhes_cargas.cargas.length === 0">
                      <td colspan="3" class="px-4 py-6 text-center text-surface-400 italic font-medium">Esta fatura não possui cargas variáveis vinculadas.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="bg-surface-50 px-6 py-4 flex flex-row-reverse border-t border-surface-200">
            <button @click="fecharModalDetalhes" class="w-full sm:w-auto px-6 py-2.5 bg-white border border-surface-300 rounded-lg text-sm font-bold text-surface-700 hover:bg-surface-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-surface-500">Fechar</button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showModalPagamento" class="fixed inset-0 z-modal overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-surface-950/80 transition-opacity backdrop-blur-sm" @click="fecharModalPagamento"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-surface-200">
          <div class="bg-white px-6 pt-6 pb-8 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-emerald-100 mb-4">
              <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-1">Pagamento via PIX</h3>
            <p class="text-sm text-surface-500 mb-6">Competência {{ faturaPagamento?.mes_referencia }} • Valor: <strong class="text-surface-900">{{ formatarMoeda(faturaPagamento?.valor_total) }}</strong></p>
            
            <div class="bg-surface-50 p-4 rounded-xl border border-surface-200 mb-6">
              <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" class="w-40 h-40 mx-auto mb-4 opacity-80 mix-blend-multiply" />
              <p class="text-xs font-bold text-surface-500 uppercase mb-2">Pix Copia e Cola</p>
              <div class="flex items-center bg-white border border-surface-300 rounded p-2">
                <input type="text" readonly value="00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-426655440000" class="flex-1 text-xs font-mono bg-transparent outline-none text-surface-600" id="pixCode" />
                <button @click="copiarPix" class="ml-2 text-brand-600 font-bold text-xs hover:text-brand-800 uppercase focus:outline-none">Copiar</button>
              </div>
            </div>
            
            <button @click="simularPagamentoAprovado" :disabled="simulandoPagamento" class="w-full bg-surface-900 text-white font-black py-3 rounded-lg hover:bg-surface-800 transition-colors disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-surface-500 shadow-clinical-sm">
              {{ simulandoPagamento ? 'Processando Baixa...' : '[DEV] Simular Pagamento Aprovado' }}
            </button>
          </div>
          <div class="bg-surface-50 px-6 py-4 flex border-t border-surface-200">
            <button @click="fecharModalPagamento" class="w-full text-center text-sm font-bold text-surface-600 hover:text-surface-900 transition-colors focus:outline-none">Cancelar e Voltar</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const faturas = ref([]);
const loading = ref(true);

const showModalDetalhes = ref(false);
const showModalPagamento = ref(false);
const faturaSelecionada = ref(null);
const faturaPagamento = ref(null);
const carregandoDetalhes = ref(false);
const simulandoPagamento = ref(false);

const formatarMoeda = (valor) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);
const formatarData = (dataString) => dataString ? new Date(dataString).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) : '--';
const isVencida = (f) => f && f.status !== 'paga' && new Date(f.data_vencimento) < new Date();
const getStatusClass = (f) => {
  if (!f) return '';
  if (f.status === 'paga') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (isVencida(f)) return 'bg-rose-50 text-rose-700 border-rose-200';
  return 'bg-amber-50 text-amber-700 border-amber-200';
};

const fetchFaturas = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/embarcador/faturas');
    // Suporte direto à estrutura de paginação implementada no controller
    faturas.value = res.data.data || res.data;
  } catch (err) {
    console.error('Falha de I/O ao buscar listagem', err);
  } finally {
    loading.value = false;
  }
};

const abrirDetalhes = async (id) => {
  showModalDetalhes.value = true;
  carregandoDetalhes.value = true;
  faturaSelecionada.value = null;
  try {
    // Lazy Load Obrigatório: Busca o JSON pesado apenas sob demanda para um ID específico.
    const res = await axios.get(`/api/v1/embarcador/faturas/${id}`);
    faturaSelecionada.value = res.data;
  } catch (err) {
    alert('Erro ao processar telemetria da fatura.');
    showModalDetalhes.value = false;
  } finally {
    carregandoDetalhes.value = false;
  }
};

const fecharModalDetalhes = () => { 
  showModalDetalhes.value = false; 
  faturaSelecionada.value = null; 
};

const abrirPagamento = (fatura) => {
  faturaPagamento.value = fatura;
  showModalPagamento.value = true;
};

const fecharModalPagamento = () => {
  if (simulandoPagamento.value) return;
  showModalPagamento.value = false;
  faturaPagamento.value = null;
};

const copiarPix = () => {
  const pixInput = document.getElementById('pixCode');
  pixInput.select();
  document.execCommand('copy');
  alert('Código PIX Copiado!');
};

const simularPagamentoAprovado = async () => {
  simulandoPagamento.value = true;
  try {
    await new Promise(r => setTimeout(r, 1500));
    const index = faturas.value.findIndex(f => f.id === faturaPagamento.value.id);
    if (index !== -1) {
      faturas.value[index].status = 'paga';
    }
    alert('✅ Pagamento Confirmado. O gateway disparará a baixa oficial via Webhook em background.');
    fecharModalPagamento();
  } catch (e) {
    alert('Erro no gateway.');
  } finally {
    simulandoPagamento.value = false;
  }
};

onMounted(fetchFaturas);
</script>