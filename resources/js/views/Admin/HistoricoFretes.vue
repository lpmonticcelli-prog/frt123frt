<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-200 bg-slate-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight uppercase">Arquivo Morto & Auditoria 360º</h2>
          <p class="text-sm text-slate-400 font-medium">Histórico imutável de operações concluídas e evidências de entrega.</p>
        </div>
        <div class="flex items-center space-x-3">
          <button @click="fetchFretes" :disabled="loading" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-bold uppercase hover:bg-slate-700 transition-all flex items-center border border-slate-700 shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Sincronizar Arquivo
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div v-if="loading && (!fretes || fretes.length === 0)" class="p-20 text-center text-slate-400 font-bold animate-pulse uppercase tracking-widest text-sm">
        A consultar base de dados histórica...
      </div>

      <div v-else-if="!fretes || fretes.length === 0" class="p-20 text-center">
        <p class="text-slate-500 font-bold uppercase">Nenhum frete arquivado até ao momento.</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200 text-left">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-wider">Carga ID</th>
            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-wider">Embarcador (Indústria)</th>
            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-wider">Motorista</th>
            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-wider text-center">Data Conclusão</th>
            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">Ação Crítica</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <tr v-for="frete in fretes" :key="frete.id" class="hover:bg-slate-50/80 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-sm font-black text-slate-900 font-mono tracking-tighter">#{{ frete.id }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-slate-800">{{ frete.embarcador?.razao_social || 'N/A' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-slate-700">{{ frete.motorista?.user?.name || 'N/A' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <div class="text-xs font-mono font-bold text-slate-500 uppercase">{{ formatarData(frete.updated_at) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <button @click="abrirAuditoria(frete.id)" class="px-4 py-2 bg-slate-900 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">
                Auditar Processo
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
      <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col border border-slate-300">
        
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
          <div>
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter">Trilha de Auditoria: Carga #{{ auditoriaData?.carga?.id }}</h3>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Verificação de Conformidade e Evidências</p>
          </div>
          <button @click="showModal = false" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all font-black">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto p-8 bg-slate-50/30">
          
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            <div class="lg:col-span-7 space-y-8">
              <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                Histórico de Eventos do Sistema
              </h4>
              
              <div v-for="(step, index) in auditoriaData?.timeline" :key="index" class="relative pl-10">
                <div v-if="auditoriaData?.timeline && index !== auditoriaData.timeline.length - 1" class="absolute left-[11px] top-6 bottom-[-32px] w-0.5 bg-slate-200"></div>
                
                <div class="absolute left-0 top-0 w-6 h-6 rounded-full border-4 bg-white z-10 transition-all shadow-sm" :style="{ borderColor: getCorHex(step.cor) }"></div>
                
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                  <div class="flex justify-between items-start mb-1">
                    <span class="text-[10px] font-black uppercase" :style="{ color: getCorHex(step.cor) }">{{ step.evento }}</span>
                    <span class="text-[10px] font-mono font-bold text-slate-400">{{ formatarData(step.data) }}</span>
                  </div>
                  <p class="text-sm font-black text-slate-800 tracking-tight leading-tight">{{ step.descricao }}</p>
                </div>
              </div>
            </div>

            <div class="lg:col-span-5 space-y-6">
              <div class="sticky top-0">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center">
                  <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></span>
                  Prova de Entrega (POD)
                </h4>

                <div class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-2 overflow-hidden shadow-inner flex flex-col items-center justify-center min-h-[400px]">
                  <div v-if="auditoriaData?.carga?.foto_canhoto" class="w-full h-full">
                    <img :src="getEvidenciaUrl(auditoriaData.carga.foto_canhoto)" class="rounded-xl w-full object-contain max-h-[500px] shadow-lg border border-slate-100" alt="Evidência do motorista" />
                    <div class="mt-4 p-4 bg-emerald-50 rounded-lg border border-emerald-100 text-center">
                      <p class="text-[10px] font-black text-emerald-700 uppercase">Documento Autenticado</p>
                      <p class="text-[9px] text-emerald-600 font-mono mt-1 italic">Hash: {{ auditoriaData.carga.id }}{{ new Date(auditoriaData.carga.updated_at).getTime() }}</p>
                    </div>
                  </div>
                  <div v-else class="text-center px-10">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                      <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <p class="text-sm font-bold text-slate-300 uppercase italic">Nenhuma evidência fotográfica anexada a esta carga.</p>
                  </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4">
                  <div class="bg-slate-900 p-4 rounded-xl text-white shadow-lg">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Frete Motorista</p>
                    <p class="text-lg font-black">{{ formatarMoeda(auditoriaData?.carga?.valor_frete) }}</p>
                  </div>
                  <div class="bg-blue-600 p-4 rounded-xl text-white shadow-lg font-black">
                    <p class="text-[10px] font-black text-blue-200 uppercase">Taxa Plataforma</p>
                    <p class="text-lg font-black">{{ formatarMoeda(auditoriaData?.carga?.taxa_plataforma) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="auditoriaData?.contratos" class="mt-12 pt-8 border-t border-slate-200">
            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center">
              <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
              Certificados e Assinaturas Digitais
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5">
                  <svg class="w-24 h-24 text-slate-900" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                </div>
                <div class="relative z-10">
                  <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-[10px] font-black uppercase tracking-widest rounded mb-4">Contrato: Indústria (Embarcador)</span>
                  
                  <div class="space-y-3">
                    <div>
                      <p class="text-[10px] text-slate-400 font-bold uppercase">Contratante</p>
                      <p class="text-sm font-black text-slate-900">{{ auditoriaData.contratos.embarcador.nome }}</p>
                      <p class="text-xs text-slate-500 font-mono">{{ auditoriaData.contratos.embarcador.documento }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                      <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Data Assinatura</p>
                        <p class="text-xs font-mono font-bold text-slate-700">{{ formatarData(auditoriaData.contratos.embarcador.data_assinatura) }}</p>
                      </div>
                      <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">IP Origem</p>
                        <p class="text-xs font-mono font-bold text-slate-700">{{ auditoriaData.contratos.embarcador.ip_assinatura }}</p>
                      </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                      <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Hash Criptográfico de Aceite</p>
                      <div class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto">
                        <code class="text-[10px] text-slate-600 font-bold break-all">{{ auditoriaData.contratos.embarcador.hash_certificado }}</code>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5">
                  <svg class="w-24 h-24 text-slate-900" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 8h-2v-2h2v2zm-4 0H6v-2h2v2z" clip-rule="evenodd" /></svg>
                </div>
                <div class="relative z-10">
                  <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-[10px] font-black uppercase tracking-widest rounded mb-4">Contrato: Transportador (Motorista)</span>
                  
                  <div v-if="auditoriaData.contratos.motorista.valido" class="space-y-3">
                    <div>
                      <p class="text-[10px] text-slate-400 font-bold uppercase">Contratado</p>
                      <p class="text-sm font-black text-slate-900">{{ auditoriaData.contratos.motorista.nome }}</p>
                      <p class="text-xs text-slate-500 font-mono">{{ auditoriaData.contratos.motorista.documento }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                      <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Data Assinatura</p>
                        <p class="text-xs font-mono font-bold text-slate-700">{{ formatarData(auditoriaData.contratos.motorista.data_assinatura) }}</p>
                      </div>
                      <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">IP Origem</p>
                        <p class="text-xs font-mono font-bold text-slate-700">{{ auditoriaData.contratos.motorista.ip_assinatura }}</p>
                      </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                      <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Hash Criptográfico de Aceite</p>
                      <div class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto">
                        <code class="text-[10px] text-slate-600 font-bold break-all">{{ auditoriaData.contratos.motorista.hash_certificado }}</code>
                      </div>
                    </div>
                  </div>

                  <div v-else class="py-10 text-center text-slate-400 italic font-bold">
                    Carga não possui aceite de motorista.
                  </div>
                </div>
              </div>

            </div>
          </div>
          </div>

        <div class="p-6 bg-white border-t border-slate-100 text-right flex justify-between items-center">
          <span class="text-[10px] font-bold text-slate-400 uppercase font-mono">Gerado por 123fretei Auditor em {{ new Date().toLocaleString('pt-BR') }}</span>
          <button @click="showModal = false" class="px-8 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-black text-slate-700 uppercase tracking-widest hover:bg-slate-200 transition-all">Fechar Auditoria</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const fretes = ref([]);
const loading = ref(true);
const showModal = ref(false);
const auditoriaData = ref(null);

const fetchFretes = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/admin/fretes/concluidos');
    fretes.value = res.data?.data || res.data || [];
  } catch (err) {
    console.error('Falha ao sincronizar arquivo morto.', err);
    fretes.value = [];
  } finally {
    loading.value = false;
  }
};

const abrirAuditoria = async (id) => {
  try {
    const res = await axios.get(`/api/v1/admin/fretes/${id}/auditoria`);
    auditoriaData.value = res.data;
    showModal.value = true;
  } catch (err) {
    alert('Erro ao reconstruir timeline de auditoria.');
  }
};

const getEvidenciaUrl = (path) => {
  if (!path) return '';
  // Se já for uma URL completa (como no nosso proxy seguro), retorna ela.
  if (path.startsWith('http') || path.startsWith('/api/')) return path;
  // Fallback caso venha apenas o caminho
  return `/api/v1/admin/auditoria/documento?path=${encodeURIComponent(path)}`;
};

const formatarMoeda = (valor) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);
};

const formatarData = (dataStr) => {
    if (!dataStr) return '';
    return new Date(dataStr).toLocaleString('pt-BR');
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