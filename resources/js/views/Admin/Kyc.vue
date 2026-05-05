<template>
  <div class="space-y-6">
    
    <div class="flex justify-between items-center bg-white p-6 rounded-lg shadow-sm border border-gray-200">
      <div>
        <h2 class="text-2xl font-black text-gray-800 tracking-tight">Fila de Aprovação (KYC)</h2>
        <p class="text-sm text-gray-500 mt-1">Analise os documentos pendentes de novos motoristas e embarcadores.</p>
      </div>
      <div class="text-right">
        <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider border border-yellow-200">
          {{ usuariosPendentes.length }} Pendentes
        </span>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Solicitante</th>
            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Papel</th>
            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Documento (CPF/CNPJ)</th>
            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Data do Cadastro</th>
            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Ação</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading" class="animate-pulse">
            <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-bold">Carregando dados da fila...</td>
          </tr>
          
          <tr v-else-if="usuariosPendentes.length === 0">
            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
              <p class="font-bold text-lg text-gray-900">Fila Limpa</p>
              <p class="text-sm mt-1">Todos os usuários pendentes foram analisados.</p>
            </td>
          </tr>

          <tr v-else v-for="user in usuariosPendentes" :key="user.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="font-bold text-gray-900">{{ user.name }}</div>
              <div class="text-sm text-gray-500">{{ user.email }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span 
                class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-md uppercase"
                :class="user.role?.slug === 'motorista' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'"
              >
                {{ user.role?.name }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600">
              {{ obterDocumentoPrincipal(user) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ new Date(user.created_at).toLocaleDateString('pt-BR') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
              <button 
                @click="abrirAnalise(user)" 
                class="bg-gray-900 text-white px-4 py-2 rounded font-bold text-xs hover:bg-gray-800 transition-colors shadow-sm"
              >
                Analisar Dossiê
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="modalAberto" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 backdrop-blur-sm">
      <div class="bg-white w-full max-w-6xl rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
          <h3 class="text-lg font-black text-gray-900">Mesa de Análise: <span class="text-blue-600">{{ usuarioSendoAnalisado.name }}</span></h3>
          <button @click="fecharAnalise" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 flex flex-col lg:flex-row gap-6">
          
          <div class="w-full lg:w-1/3 space-y-6 flex flex-col">
            <div>
              <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Dados Pessoais</h4>
              <div class="bg-gray-50 p-4 rounded border border-gray-200 space-y-3">
                <div><span class="block text-xs text-gray-500">E-mail:</span><strong class="text-sm text-gray-800">{{ usuarioSendoAnalisado.email }}</strong></div>
                <div><span class="block text-xs text-gray-500">Telefone:</span><strong class="text-sm text-gray-800">{{ usuarioSendoAnalisado.phone }}</strong></div>
                <div><span class="block text-xs text-gray-500">Papel:</span><strong class="text-sm text-gray-800 uppercase">{{ usuarioSendoAnalisado.role?.name }}</strong></div>
                <div><span class="block text-xs text-gray-500">Documento Principal:</span><strong class="text-sm text-gray-800">{{ obterDocumentoPrincipal(usuarioSendoAnalisado) }}</strong></div>
              </div>
            </div>

            <div class="flex-1">
              <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Arquivos Enviados</h4>
              <div class="space-y-2">
                <template v-if="usuarioSendoAnalisado.role?.slug === 'motorista'">
                  <button @click="selecionarDocumento(usuarioSendoAnalisado.motorista?.doc_cnh)" :class="btnDocClass(usuarioSendoAnalisado.motorista?.doc_cnh)">1. Carteira de Habilitação (CNH)</button>
                  <button @click="selecionarDocumento(usuarioSendoAnalisado.motorista?.doc_selfie_cnh)" :class="btnDocClass(usuarioSendoAnalisado.motorista?.doc_selfie_cnh)">2. Selfie com CNH</button>
                  <button @click="selecionarDocumento(usuarioSendoAnalisado.motorista?.doc_rntrc)" :class="btnDocClass(usuarioSendoAnalisado.motorista?.doc_rntrc)">3. Registo RNTRC</button>
                  <button @click="selecionarDocumento(usuarioSendoAnalisado.motorista?.doc_comprovante_endereco)" :class="btnDocClass(usuarioSendoAnalisado.motorista?.doc_comprovante_endereco)">4. Comprovativo de Endereço</button>
                </template>
                <template v-else-if="usuarioSendoAnalisado.role?.slug === 'embarcador'">
                  <button @click="selecionarDocumento(usuarioSendoAnalisado.embarcador?.documento_kyc)" :class="btnDocClass(usuarioSendoAnalisado.embarcador?.documento_kyc)">1. Cartão CNPJ / Contrato Social</button>
                </template>
              </div>
            </div>

            <div v-if="modoRejeicao" class="bg-red-50 p-4 rounded border border-red-200 mt-auto">
              <label class="block text-xs font-bold text-red-800 uppercase mb-2">Motivo da Rejeição (Log Auditável)</label>
              <textarea 
                v-model="motivoRejeicao" 
                rows="3" 
                class="w-full text-sm border-red-300 rounded focus:ring-red-500 focus:border-red-500 p-2"
                placeholder="Ex: CNH ilegível, validade expirada..."
              ></textarea>
            </div>
          </div>

          <div class="w-full lg:w-2/3 bg-gray-100 rounded border border-gray-200 p-2 flex flex-col items-center justify-center relative min-h-[500px]">
             <div v-if="!docAtivo" class="text-gray-400 font-bold uppercase tracking-widest text-sm">
                Nenhum documento selecionado ou enviado
             </div>
             
             <iframe 
                v-else 
                :src="`/storage/${docAtivo}`" 
                class="w-full h-full rounded z-10 border-0 bg-white"
             ></iframe>
          </div>

        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
          
          <template v-if="!modoRejeicao">
            <button @click="fecharAnalise" class="px-5 py-2 rounded text-sm font-bold text-gray-600 hover:bg-gray-200 transition-colors">Cancelar</button>
            <button @click="ativarModoRejeicao" class="px-5 py-2 rounded text-sm font-bold bg-red-100 text-red-700 hover:bg-red-200 border border-red-200 transition-colors">Rejeitar Cadastro</button>
            <button @click="processarAcao('active')" :disabled="loadingAprovacao" class="px-6 py-2 rounded text-sm font-bold bg-green-600 text-white hover:bg-green-700 shadow flex items-center transition-colors disabled:opacity-50">
              <span v-if="loadingAprovacao">Processando...</span>
              <span v-else>Aprovar & Liberar Acesso</span>
            </button>
          </template>
          
          <template v-else>
            <button @click="modoRejeicao = false" class="px-5 py-2 rounded text-sm font-bold text-gray-600 hover:bg-gray-200 transition-colors">Voltar</button>
            <button @click="processarAcao('rejected')" :disabled="!motivoRejeicao || loadingAprovacao" class="px-6 py-2 rounded text-sm font-bold bg-red-600 text-white hover:bg-red-700 shadow disabled:opacity-50 transition-colors">
              <span v-if="loadingAprovacao">Processando...</span>
              <span v-else>Confirmar Rejeição Definitiva</span>
            </button>
          </template>

        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// --- ESTADO REATIVO ---
const usuariosPendentes = ref([]);
const loading = ref(true);

const modalAberto = ref(false);
const usuarioSendoAnalisado = ref(null);
const docAtivo = ref(null);
const modoRejeicao = ref(false);
const motivoRejeicao = ref('');
const loadingAprovacao = ref(false);

// --- INTEGRAÇÃO COM API ---
const carregarFila = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/admin/usuarios-pendentes');
    usuariosPendentes.value = response.data;
  } catch (error) {
    console.error('Erro ao carregar KYC:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  carregarFila();
});

// --- HELPER DE DADOS ---
const obterDocumentoPrincipal = (user) => {
  if (user.role?.slug === 'motorista') return user.motorista?.cpf || 'Não informado';
  if (user.role?.slug === 'embarcador') return user.embarcador?.cnpj || 'Não informado';
  return 'N/A';
};

const btnDocClass = (caminhoDoc) => {
  const base = "w-full text-left px-4 py-3 text-sm font-bold border rounded transition-colors block ";
  if (!caminhoDoc) return base + "bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed opacity-60";
  if (docAtivo.value === caminhoDoc) return base + "bg-blue-50 text-blue-700 border-blue-500 shadow-sm";
  return base + "bg-white text-gray-700 border-gray-300 hover:bg-gray-50";
};

// --- MÉTODOS DE CONTROLE ---
const abrirAnalise = (user) => {
  usuarioSendoAnalisado.value = user;
  modoRejeicao.value = false;
  motivoRejeicao.value = '';
  
  // Seleciona automaticamente o primeiro documento disponível dependendo do tipo
  if (user.role?.slug === 'motorista') {
    docAtivo.value = user.motorista?.doc_cnh || user.motorista?.doc_selfie_cnh || null;
  } else {
    docAtivo.value = user.embarcador?.documento_kyc || null;
  }

  modalAberto.value = true;
};

const selecionarDocumento = (caminho) => {
  if (caminho) docAtivo.value = caminho;
};

const fecharAnalise = () => {
  modalAberto.value = false;
  usuarioSendoAnalisado.value = null;
  docAtivo.value = null;
};

const ativarModoRejeicao = () => {
  modoRejeicao.value = true;
};

const processarAcao = async (statusFinal) => {
  loadingAprovacao.value = true;
  try {
    // Opcional: Se sua API suportar envio do motivo da rejeição, ele vai no payload
    const payload = { 
      status: statusFinal,
      motivo: statusFinal === 'rejected' ? motivoRejeicao.value : null
    };
    
    await axios.post(`/api/admin/usuarios/${usuarioSendoAnalisado.value.id}/analise`, payload);
    
    fecharAnalise();
    carregarFila(); // Atualiza a tabela após a ação
  } catch (error) {
    console.error('Erro na auditoria:', error);
    alert(error.response?.data?.message || 'Erro ao processar a análise do usuário.');
  } finally {
    loadingAprovacao.value = false;
  }
};
</script>