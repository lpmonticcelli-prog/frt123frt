<template>
  <div class="w-full relative min-h-screen bg-slate-50 pb-12 pt-8 px-4 sm:px-6">
    <div class="max-w-screen-xl mx-auto space-y-6">
      
      <!-- HEADER DO PAINEL -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-6 rounded-2xl border border-slate-200 shadow-sm gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Gestão Financeira & Faturamento</h2>
          <p class="text-sm text-slate-500 mt-1 font-medium">Gira as faturas consolidadas e repasses da plataforma.</p>
        </div>
        <button @click="fetchFaturas" :disabled="loading" class="w-full sm:w-auto px-5 py-2.5 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors disabled:opacity-50 flex items-center justify-center shadow-sm focus:outline-none">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          {{ loading ? 'Sincronizando Malha...' : 'Atualizar Faturas' }}
        </button>
      </div>

      <!-- CONTAINER DA TABELA -->
      <div class="bg-transparent lg:bg-white lg:rounded-2xl lg:shadow-sm lg:border border-slate-200 overflow-hidden">
        
        <!-- ESTADO: CARREGANDO -->
        <div v-if="loading && faturas.length === 0" class="p-16 text-center text-slate-500 font-medium text-sm flex flex-col items-center bg-white rounded-2xl shadow-sm border border-slate-200 lg:border-none lg:shadow-none">
          <svg class="w-10 h-10 animate-spin text-[#ff5500] mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          A consultar motor de faturamento...
        </div>

        <!-- ESTADO: VAZIO -->
        <div v-else-if="faturas.length === 0" class="p-16 text-center bg-white rounded-2xl shadow-sm border border-slate-200 lg:border-none lg:shadow-none">
          <div class="mx-auto w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5 border border-slate-100 shadow-inner">
            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          </div>
          <h3 class="text-xl font-bold text-slate-900 tracking-tight">Nenhuma fatura gerada</h3>
          <p class="text-slate-500 mt-2 max-w-md mx-auto">Sua infraestrutura logística ainda não gerou cobranças no ciclo atual.</p>
        </div>

        <!-- ESTADO: COM FATURAS -->
        <div v-else class="w-full">
          <table class="min-w-full text-left border-collapse block lg:table">
            <thead class="bg-slate-50 hidden lg:table-header-group border-b border-slate-200">
              <tr>
                <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Referência</th>
                <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Emissão</th>
                <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Vencimento</th>
                <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Valor Total</th>
                <th scope="col" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest">Status</th>
                <th scope="col" class="px-6 py-5 text-right text-xs font-black text-slate-500 uppercase tracking-widest">Ações</th>
              </tr>
            </thead>
            <tbody class="block lg:table-row-group divide-y-0 lg:divide-y divide-slate-100" :class="{ 'opacity-50 pointer-events-none': loading }">
              
              <tr v-for="fatura in faturas" :key="fatura.id" class="block lg:table-row bg-white hover:bg-slate-50/80 transition-colors mb-6 lg:mb-0 rounded-2xl lg:rounded-none shadow-sm lg:shadow-none border border-slate-200 lg:border-none overflow-hidden">
                
                <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                  <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Referência</div>
                  <div class="text-sm font-extrabold text-slate-900 font-mono">Comp. {{ fatura.mes_referencia }}</div>
                </td>
                
                <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                  <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Emissão</div>
                  <div class="text-sm font-bold text-slate-700">{{ formatarData(fatura.created_at) }}</div>
                </td>
                
                <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                  <div class="flex lg:block justify-between items-center">
                    <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest">Vencimento</div>
                    <div :class="['text-sm font-black', isVencida(fatura) ? 'text-red-600' : 'text-slate-900']">
                      {{ formatarData(fatura.data_vencimento) }}
                    </div>
                  </div>
                </td>
                
                <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                  <div class="flex lg:block justify-between items-center">
                    <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest">Valor Total</div>
                    <div class="text-sm font-black text-[#035D29] tabular-nums">{{ formatarMoeda(fatura.valor_total) }}</div>
                  </div>
                </td>
                
                <td class="block lg:table-cell px-5 py-4 lg:px-6 lg:py-5 border-b border-slate-100 lg:border-none">
                  <div class="flex lg:block justify-between items-center">
                    <div class="lg:hidden text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</div>
                    <span :class="['px-3 py-1.5 inline-flex text-[10px] font-black uppercase tracking-widest rounded-lg border', getStatusClass(fatura)]">
                      {{ fatura.status }}
                    </span>
                  </div>
                </td>
                
                <td class="block lg:table-cell px-5 py-5 lg:px-6 lg:py-5 bg-slate-50 lg:bg-transparent rounded-b-2xl lg:rounded-none">
                  <div class="flex flex-wrap lg:justify-end gap-2 lg:gap-3 items-center w-full">
                    <button @click="abrirDetalhes(fatura.id)" class="flex-1 lg:flex-none text-center bg-white lg:bg-transparent border lg:border-none border-slate-300 px-4 py-2.5 lg:px-2 lg:py-1 rounded-xl lg:rounded text-slate-600 hover:text-[#035D29] font-extrabold transition-colors text-xs lg:text-[10px] uppercase tracking-widest shadow-sm lg:shadow-none">
                      Detalhes
                    </button>
                    <button v-if="fatura.status === 'pendente' || fatura.status === 'vencida'" @click="abrirPagamento(fatura)" class="w-full lg:w-auto inline-flex justify-center items-center px-6 py-3 lg:py-2 bg-[#035D29] text-white font-bold text-xs rounded-xl lg:rounded-lg hover:bg-[#023818] transition-colors shadow-md">
                      Pagar Agora
                    </button>
                    <a v-if="fatura.status === 'paga' && fatura.link_boleto" :href="fatura.link_boleto" target="_blank" class="w-full lg:w-auto inline-flex justify-center items-center px-6 py-3 lg:py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs rounded-xl lg:rounded-lg hover:bg-emerald-100 transition-colors">
                      Baixar Recibo
                    </a>
                  </div>
                </td>

              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- MODAL: DETALHES DA FATURA -->
      <div v-if="showModalDetalhes" class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm" @click="fecharModalDetalhes"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
          
          <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-slate-200">
            <div class="bg-white px-6 pt-6 pb-6 sm:p-8">
              <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-6">
                <div>
                  <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tight">Composição da Fatura</h3>
                  <p class="text-sm text-slate-500 font-mono mt-1 font-medium">Competência: {{ faturaSelecionada?.mes_referencia || 'Carregando...' }}</p>
                </div>
                <span v-if="faturaSelecionada" :class="['px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg border', getStatusClass(faturaSelecionada)]">
                  {{ faturaSelecionada.status }}
                </span>
              </div>
              
              <div v-if="carregandoDetalhes" class="py-16 text-center text-slate-500 font-bold tracking-wide animate-pulse">
                Descompactando logs e composição de cargas associadas...
              </div>
              
              <div v-else-if="faturaSelecionada">
                 <!-- Resumo Financeiro -->
                 <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 mb-8">
                  <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Resumo de Custos (Taxa 123fretei)</h4>
                  <div class="flex justify-between text-sm font-bold text-slate-700 mb-3">
                    <span>Mensalidade Fixa SaaS</span>
                    <span>{{ formatarMoeda(faturaSelecionada.detalhes_cargas?.resumo?.mensalidade_fixa || 0) }}</span>
                  </div>
                  <div class="flex justify-between text-sm font-bold text-slate-700 mb-5 border-b border-slate-200 pb-5">
                    <span>Total Taxas Variáveis (Comissões de Carga)</span>
                    <span>{{ formatarMoeda(faturaSelecionada.detalhes_cargas?.resumo?.total_taxas_variaveis || 0) }}</span>
                  </div>
                  <div class="flex justify-between text-xl font-black text-[#035D29]">
                    <span>TOTAL A PAGAR</span>
                    <span>{{ formatarMoeda(faturaSelecionada.valor_total) }}</span>
                  </div>
                </div>

                <!-- Relação de Cargas -->
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Relação de Cargas Entregues no Mês</h4>
                <div class="max-h-72 overflow-y-auto border border-slate-200 rounded-xl scrollbar-clinical">
                  <table class="min-w-full divide-y divide-slate-100 text-sm text-left">
                    <thead class="bg-slate-50 sticky top-0">
                      <tr>
                        <th class="px-5 py-3 font-black text-slate-500 text-xs uppercase tracking-widest">Carga ID</th>
                        <th class="px-5 py-3 font-black text-slate-500 text-xs uppercase tracking-widest">Rota</th>
                        <th class="px-5 py-3 font-black text-slate-500 text-xs uppercase tracking-widest text-right">Taxa Cobrada</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                      <tr v-for="carga in faturaSelecionada.detalhes_cargas?.cargas || []" :key="carga.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 font-mono font-extrabold text-slate-800">#{{ carga.id }}</td>
                        <td class="px-5 py-4 text-slate-600 font-medium">{{ carga.rota || 'Rota Desconhecida' }}</td>
                        <td class="px-5 py-4 text-right font-bold text-[#035D29]">{{ formatarMoeda(carga.taxa_cobrada) }}</td>
                      </tr>
                      <tr v-if="!faturaSelecionada.detalhes_cargas?.cargas || faturaSelecionada.detalhes_cargas.cargas.length === 0">
                        <td colspan="3" class="px-5 py-8 text-center text-slate-400 italic font-medium bg-slate-50">
                          Esta fatura não possui cargas variáveis vinculadas.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse border-t border-slate-200">
              <button @click="fecharModalDetalhes" class="w-full sm:w-auto px-8 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-100 shadow-sm transition-colors">Fechar</button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- MODAL: PAGAMENTO PIX -->
      <div v-if="showModalPagamento" class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm" @click="fecharModalPagamento"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
          
          <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-slate-200">
            <div class="bg-white px-8 pt-8 pb-8 text-center">
              <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-emerald-50 border border-emerald-100 mb-6 shadow-inner">
                <svg class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <h3 class="text-2xl font-black text-slate-900 mb-2 tracking-tight">Pagamento via PIX</h3>
              <p class="text-sm text-slate-500 mb-8 font-medium">Competência {{ faturaPagamento?.mes_referencia }} • Valor: <strong class="text-slate-900 font-black">{{ formatarMoeda(faturaPagamento?.valor_total) }}</strong></p>
              
              <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 mb-8">
                <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" class="w-48 h-48 mx-auto mb-6 opacity-90 mix-blend-multiply" />
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Pix Copia e Cola</p>
                <div class="flex items-center bg-white border border-slate-300 rounded-xl p-2 shadow-sm focus-within:ring-2 focus-within:ring-[#035D29] focus-within:border-[#035D29] transition-all">
                  <input type="text" readonly value="00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-426655440000" class="flex-1 text-xs font-mono bg-transparent outline-none text-slate-600 px-2" id="pixCode" />
                  <button @click="copiarPix" class="ml-2 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg px-4 py-2 font-bold text-xs hover:bg-[#035D29] hover:text-white hover:border-[#035D29] uppercase tracking-wider transition-colors">Copiar</button>
                </div>
              </div>
              
              <button @click="simularPagamentoAprovado" :disabled="simulandoPagamento" class="w-full bg-[#035D29] text-white font-black py-3.5 rounded-xl hover:bg-[#023818] transition-colors disabled:opacity-50 shadow-md">
                {{ simulandoPagamento ? 'Processando Baixa...' : '[DEV] Simular Pagamento Aprovado' }}
              </button>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 flex border-t border-slate-200">
              <button @click="fecharModalPagamento" class="w-full text-center text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors">Cancelar e Voltar</button>
            </div>
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

// MAPEAMENTO DE STATUS MODERNIZADO
const getStatusClass = (f) => {
  if (!f) return '';
  if (f.status === 'paga') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (isVencida(f)) return 'bg-red-50 text-red-700 border-red-200';
  return 'bg-orange-50 text-orange-700 border-orange-200';
};

const fetchFaturas = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/embarcador/faturas');
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

<style scoped>
.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px;}
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>