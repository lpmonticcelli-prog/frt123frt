<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-8">
    
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-200 bg-slate-900 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight uppercase">Arquivo Morto & Auditoria 360º</h2>
          <p class="text-sm text-slate-400 font-medium">Histórico imutável de operações concluídas e evidências de entrega.</p>
        </div>
        <div class="flex items-center space-x-3 w-full md:w-auto">
          <button @click="fetchFretes" :disabled="loading" class="w-full md:w-auto px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-bold uppercase hover:bg-slate-700 transition-all flex items-center justify-center border border-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 disabled:opacity-50">
            <svg v-if="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Sincronizar Arquivo
          </button>
        </div>
      </div>
    </div>

    <div v-if="erroApiCritico" class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl shadow-sm flex items-start sm:items-center justify-between flex-col sm:flex-row gap-4 animate-fade-in">
      <div class="flex items-start sm:items-center gap-3">
        <div class="bg-red-100 p-2 rounded-full shrink-0">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <div>
          <h4 class="font-black text-sm tracking-wide">Falha Crítica de Conexão (Erro 500)</h4>
          <p class="text-xs mt-0.5 font-medium">{{ erroApiMensagem || 'O Servidor Laravel rejeitou a consulta ao banco de dados histórico.' }}</p>
        </div>
      </div>
      <button @click="fetchFretes" class="w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500">Tentar Novamente</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div v-if="loading && (!fretes || fretes.length === 0)" class="p-20 text-center text-slate-400 font-bold animate-pulse uppercase tracking-widest text-sm">
        A consultar base de dados histórica...
      </div>

      <div v-else-if="(!fretes || fretes.length === 0) && !erroApiCritico" class="p-20 text-center">
        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
          <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
        </div>
        <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">Nenhum frete arquivado até ao momento.</p>
      </div>

      <div v-else-if="fretes.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-left">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-wider">Carga ID</th>
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-wider">Embarcador (Indústria)</th>
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-wider">Motorista</th>
              <th class="px-6 py-4 text-[11px] font-black text-slate-500 uppercase tracking-wider text-center">Data Conclusão</th>
              <th class="px-6 py-4 text-right text-[11px] font-black text-slate-500 uppercase tracking-wider">Ação Crítica</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr v-for="frete in fretes" :key="frete.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-black text-slate-900 font-mono tracking-tighter">#{{ frete.id }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-slate-800" :title="frete.embarcador?.razao_social">{{ frete.embarcador?.razao_social || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-slate-700" :title="frete.motorista?.user?.name">{{ frete.motorista?.user?.name || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <div class="text-[11px] font-mono font-bold text-slate-500 uppercase">{{ formatarData(frete.updated_at) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <button @click="abrirAuditoria(frete.id)" :disabled="actionLoading" class="px-4 py-2 bg-slate-900 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-md focus:outline-none focus:ring-2 focus:ring-slate-900 disabled:opacity-50">
                  Auditar Processo
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0 sm:scale-100" leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
      <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-0 sm:p-4 bg-slate-950/90 backdrop-blur-md">
        <div class="bg-white w-full max-w-5xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[100dvh] sm:max-h-[90vh] flex flex-col border border-slate-300">
          
          <div class="p-4 sm:p-6 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
            <div class="min-w-0 pr-4">
              <h3 class="text-lg sm:text-xl font-black text-slate-900 uppercase tracking-tighter truncate">Trilha de Auditoria: Carga #{{ auditoriaData?.carga?.id }}</h3>
              <p class="text-[10px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest truncate">Verificação de Conformidade e Evidências</p>
            </div>
            <button @click="fecharModalAuditoria" class="w-10 h-10 shrink-0 flex items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all font-black focus:outline-none focus:ring-2 focus:ring-red-500">✕</button>
          </div>

          <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50/30 scrollbar-clinical">
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
              <div class="lg:col-span-7 space-y-8">
                <h4 class="text-[11px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                  Histórico de Eventos do Sistema
                </h4>
                
                <div v-if="!auditoriaData?.timeline || auditoriaData.timeline.length === 0" class="text-sm font-bold text-slate-400 p-4 border border-dashed border-slate-300 rounded-lg text-center">
                  Timeline indisponível ou corrompida.
                </div>

                <div v-for="(step, index) in auditoriaData?.timeline" :key="index" class="relative pl-8 sm:pl-10">
                  <div v-if="auditoriaData?.timeline && index !== auditoriaData.timeline.length - 1" class="absolute left-[9px] sm:left-[11px] top-6 bottom-[-32px] w-0.5 bg-slate-200"></div>
                  
                  <div class="absolute left-0 top-0 w-5 h-5 sm:w-6 sm:h-6 rounded-full border-4 bg-white z-10 transition-all shadow-sm" :style="{ borderColor: getCorHex(step.cor) }"></div>
                  
                  <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-2 sm:mb-1 gap-1">
                      <span class="text-[10px] font-black uppercase" :style="{ color: getCorHex(step.cor) }">{{ step.evento }}</span>
                      <span class="text-[10px] font-mono font-bold text-slate-400">{{ formatarData(step.data) }}</span>
                    </div>
                    <p class="text-xs sm:text-sm font-black text-slate-800 tracking-tight leading-relaxed">{{ step.descricao }}</p>
                  </div>
                </div>
              </div>

              <div class="lg:col-span-5 space-y-6">
                <div class="sticky top-0 space-y-6">
                  <div>
                    <h4 class="text-[11px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center">
                      <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></span>
                      Prova de Entrega (POD)
                    </h4>

                    <div class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-2 overflow-hidden shadow-inner flex flex-col items-center justify-center min-h-[300px] sm:min-h-[400px]">
                      <div v-if="auditoriaData?.carga?.foto_canhoto" class="w-full h-full">
                        <img :src="getEvidenciaUrl(auditoriaData.carga.foto_canhoto)" class="rounded-xl w-full object-contain max-h-[400px] sm:max-h-[500px] shadow-lg border border-slate-100" alt="Evidência do motorista" loading="lazy" />
                        <div class="mt-4 p-4 bg-emerald-50 rounded-lg border border-emerald-100 text-center">
                          <p class="text-[10px] font-black text-emerald-700 uppercase">Documento Autenticado</p>
                          <p class="text-[9px] text-emerald-600 font-mono mt-1 italic break-all">Hash: {{ auditoriaData.carga.id }}{{ new Date(auditoriaData.carga.updated_at).getTime() }}</p>
                        </div>
                      </div>
                      <div v-else class="text-center px-6 sm:px-10">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                          <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-slate-300 uppercase italic">Nenhuma evidência fotográfica anexada a esta carga.</p>
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    <div class="bg-slate-900 p-4 rounded-xl text-white shadow-lg">
                      <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase">Frete Motorista</p>
                      <p class="text-base sm:text-lg font-black tabular-nums">{{ formatarMoeda(auditoriaData?.carga?.valor_frete) }}</p>
                    </div>
                    <div class="bg-blue-600 p-4 rounded-xl text-white shadow-lg font-black">
                      <p class="text-[9px] sm:text-[10px] font-black text-blue-200 uppercase">Taxa Plataforma</p>
                      <p class="text-base sm:text-lg font-black tabular-nums">{{ formatarMoeda(auditoriaData?.carga?.taxa_plataforma) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="auditoriaData?.contratos" class="mt-10 sm:mt-12 pt-8 border-t border-slate-200">
              <h4 class="text-[11px] sm:text-xs font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                Certificados e Assinaturas Digitais
              </h4>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 sm:p-6 relative overflow-hidden">
                  <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none" aria-hidden="true">
                    <svg class="w-24 h-24 text-slate-900" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                  </div>
                  <div class="relative z-10">
                    <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-[9px] sm:text-[10px] font-black uppercase tracking-widest rounded mb-4">Contrato: Indústria (Embarcador)</span>
                    
                    <div class="space-y-3">
                      <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Contratante</p>
                        <p class="text-sm font-black text-slate-900 truncate" :title="auditoriaData.contratos.embarcador.nome">{{ auditoriaData.contratos.embarcador.nome }}</p>
                        <p class="text-xs text-slate-500 font-mono">{{ auditoriaData.contratos.embarcador.documento }}</p>
                      </div>
                      
                      <div class="grid grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                        <div>
                          <p class="text-[10px] text-slate-400 font-bold uppercase">Data Assinatura</p>
                          <p class="text-[11px] sm:text-xs font-mono font-bold text-slate-700">{{ formatarData(auditoriaData.contratos.embarcador.data_assinatura) }}</p>
                        </div>
                        <div>
                          <p class="text-[10px] text-slate-400 font-bold uppercase">IP Origem</p>
                          <p class="text-[11px] sm:text-xs font-mono font-bold text-slate-700">{{ auditoriaData.contratos.embarcador.ip_assinatura }}</p>
                        </div>
                      </div>

                      <div class="pt-3 border-t border-slate-100">
                        <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Hash Criptográfico de Aceite</p>
                        <div class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto scrollbar-hide">
                          <code class="text-[10px] text-slate-600 font-bold break-all">{{ auditoriaData.contratos.embarcador.hash_certificado }}</code>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 sm:p-6 relative overflow-hidden">
                  <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none" aria-hidden="true">
                    <svg class="w-24 h-24 text-slate-900" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 8h-2v-2h2v2zm-4 0H6v-2h2v2z" clip-rule="evenodd" /></svg>
                  </div>
                  <div class="relative z-10">
                    <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-[9px] sm:text-[10px] font-black uppercase tracking-widest rounded mb-4">Contrato: Transportador (Motorista)</span>
                    
                    <div v-if="auditoriaData.contratos.motorista.valido" class="space-y-3">
                      <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Contratado</p>
                        <p class="text-sm font-black text-slate-900 truncate" :title="auditoriaData.contratos.motorista.nome">{{ auditoriaData.contratos.motorista.nome }}</p>
                        <p class="text-xs text-slate-500 font-mono">{{ auditoriaData.contratos.motorista.documento }}</p>
                      </div>
                      
                      <div class="grid grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                        <div>
                          <p class="text-[10px] text-slate-400 font-bold uppercase">Data Assinatura</p>
                          <p class="text-[11px] sm:text-xs font-mono font-bold text-slate-700">{{ formatarData(auditoriaData.contratos.motorista.data_assinatura) }}</p>
                        </div>
                        <div>
                          <p class="text-[10px] text-slate-400 font-bold uppercase">IP Origem</p>
                          <p class="text-[11px] sm:text-xs font-mono font-bold text-slate-700">{{ auditoriaData.contratos.motorista.ip_assinatura }}</p>
                        </div>
                      </div>

                      <div class="pt-3 border-t border-slate-100">
                        <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Hash Criptográfico de Aceite</p>
                        <div class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto scrollbar-hide">
                          <code class="text-[10px] text-slate-600 font-bold break-all">{{ auditoriaData.contratos.motorista.hash_certificado }}</code>
                        </div>
                      </div>
                    </div>

                    <div v-else class="py-10 text-center text-slate-400 italic font-bold text-xs">
                      Carga não possui aceite criptográfico de motorista.
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="p-4 sm:p-6 bg-white border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 shrink-0">
            <span class="text-[9px] sm:text-[10px] font-bold text-slate-400 uppercase font-mono text-center sm:text-left">Gerado por 123fretei Auditor em {{ new Date().toLocaleString('pt-BR') }}</span>
            <button @click="fecharModalAuditoria" class="w-full sm:w-auto px-8 py-3 sm:py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-black text-slate-700 uppercase tracking-widest hover:bg-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400">Fechar Auditoria</button>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Estados
const fretes = ref([]);
const loading = ref(true);
const actionLoading = ref(false);

// Controle de Erros (Defensive Programming)
const erroApiCritico = ref(false);
const erroApiMensagem = ref('');

// Auditoria
const showModal = ref(false);
const auditoriaData = ref(null);

const fetchFretes = async () => {
  loading.value = true;
  erroApiCritico.value = false;
  erroApiMensagem.value = '';

  try {
    const res = await axios.get('/api/v1/admin/fretes/concluidos');
    fretes.value = res.data?.data || res.data || [];
  } catch (err) {
    console.error('[API] Falha ao sincronizar arquivo morto.', err);
    erroApiCritico.value = true;
    erroApiMensagem.value = err.response?.data?.message || err.response?.data?.error || 'A conexão com o servidor SQL foi recusada (Código 500).';
    fretes.value = [];
  } finally {
    loading.value = false;
  }
};

const abrirAuditoria = async (id) => {
  if (!id) return;
  actionLoading.value = true;
  
  try {
    const res = await axios.get(`/api/v1/admin/fretes/${id}/auditoria`);
    auditoriaData.value = res.data;
    showModal.value = true;
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'O Servidor rejeitou a montagem da Timeline.';
    alert(`[FALHA DE AUDITORIA] ${errorMsg}`);
  } finally {
    actionLoading.value = false;
  }
};

const fecharModalAuditoria = () => {
  showModal.value = false;
  auditoriaData.value = null;
};

// Helpers de Formatação Blindados
const getEvidenciaUrl = (path) => {
  if (!path || typeof path !== 'string') return '';
  if (path.startsWith('http') || path.startsWith('/api/')) return path;
  return `/api/v1/admin/auditoria/documento?path=${encodeURIComponent(path)}`;
};

const formatarMoeda = (valor) => {
  const num = parseFloat(valor);
  if (isNaN(num)) return 'R$ 0,00';
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num);
};

const formatarData = (dataStr) => {
    if (!dataStr) return '--';
    try {
        return new Date(dataStr).toLocaleString('pt-BR');
    } catch (e) {
        return dataStr;
    }
};

const getCorHex = (cor) => {
  const map = {
    'blue': '#3b82f6',
    'indigo': '#6366f1',
    'green': '#22c55e',
    'emerald': '#10b981',
    'orange': '#f97316',
    'red': '#ef4444'
  };
  return map[cor] || '#cbd5e1';
};

onMounted(fetchFretes);
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
.scrollbar-clinical::-webkit-scrollbar {
  width: 6px;
}
.scrollbar-clinical::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-clinical::-webkit-scrollbar-thumb {
  background-color: #94a3b8;
  border-radius: 3px;
}
.scrollbar-clinical::-webkit-scrollbar-thumb:hover {
  background-color: #64748b;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>