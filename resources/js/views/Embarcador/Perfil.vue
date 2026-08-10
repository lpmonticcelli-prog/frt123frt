<template>
  <div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between mb-2">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Configurações da Conta</h2>
        <p class="text-sm text-gray-500 mt-1">Gira os dados empresariais e a conformidade (KYC) da sua indústria.</p>
      </div>
    </div>

    <div v-if="!pageLoading" :class="statusBanner.class" class="border-l-4 p-5 rounded-r-xl shadow-sm">
      <div class="flex items-start">
        <div class="flex-shrink-0 mt-0.5">
          <span class="text-2xl">{{ statusBanner.icon }}</span>
        </div>
        <div class="ml-4">
          <h3 class="text-sm font-bold uppercase tracking-wider" :class="statusBanner.textClass">
            Status Operacional: {{ statusBanner.title }}
          </h3>
          <p class="text-sm mt-1" :class="statusBanner.textClass">
            {{ statusBanner.description }}
          </p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      
      <div v-if="pageLoading" class="flex justify-center items-center py-16">
        <div class="text-slate-500 font-bold text-sm">A carregar informações seguras da conta...</div>
      </div>

      <div v-else>
        <form @submit.prevent="updatePerfil" class="p-8 space-y-8">
          
          <div>
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-5 border-b border-slate-100 pb-3">Dados Empresariais e Fiscais</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-slate-700 mb-2">Razão Social <span class="text-red-500">*</span></label>
                <input v-model="form.razao_social" type="text" required class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm bg-slate-50 focus:bg-white transition-colors">
              </div>
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">CNPJ <span class="text-red-500">*</span></label>
                <input v-model="form.cnpj" type="text" required maxlength="18" placeholder="00.000.000/0000-00" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm bg-slate-50 focus:bg-white transition-colors font-mono">
              </div>
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Inscrição Estadual (IE)</label>
                <input v-model="form.inscricao_estadual" type="text" placeholder="ISENTO ou Número" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm bg-slate-50 focus:bg-white transition-colors font-mono">
              </div>
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Telefone / WhatsApp Comercial <span class="text-red-500">*</span></label>
                <input v-model="form.telefone" type="text" required class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-sm bg-slate-50 focus:bg-white transition-colors">
              </div>
            </div>
          </div>

          <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 shadow-inner">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Validação de Conta (KYC)</h3>
            <p class="text-sm text-slate-600 mb-5 leading-relaxed">
              Para publicar fretes e transacionar na plataforma, faça o upload do Cartão CNPJ atualizado ou do Contrato Social da empresa. A nossa equipa de Compliance aprovará a sua conta num prazo máximo de 24h.
            </p>
            
            <div class="flex items-center space-x-6 bg-white p-4 rounded-lg border border-slate-200">
              <div class="flex-1">
                <input 
                  type="file" 
                  accept=".pdf,image/png,image/jpeg" 
                  @change="handleDocumentUpload" 
                  class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer bg-white transition-colors"
                />
                <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-wider">Formatos aceitos: PDF, JPG ou PNG. Máx: 5MB.</p>
              </div>
              
              <div v-if="form.documento_kyc_url" class="flex-shrink-0 text-center border-l border-slate-200 pl-6">
                <a :href="form.documento_kyc_url" target="_blank" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex flex-col items-center transition-colors">
                  <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  Visualizar Arquivo
                </a>
              </div>
            </div>
          </div>

          <div class="pt-6 border-t border-slate-200 flex justify-end">
            <button 
              type="submit" 
              :disabled="submitLoading"
              class="px-8 py-3 bg-slate-900 text-white font-bold rounded-lg hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 disabled:opacity-50 transition-all shadow-md"
            >
              {{ submitLoading ? 'A Guardar Alterações...' : 'Salvar Informações' }}
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

// Estado higienizado: focando apenas na conformidade (KYC) e dados da empresa
const pageLoading = ref(true);
const submitLoading = ref(false);
const arquivoDocumento = ref(null);

const form = ref({
  razao_social: '',
  cnpj: '',
  inscricao_estadual: '',
  telefone: '',
  status_conta: 'pending',
  documento_kyc_url: null
});

// Lógica de UI para o Status
const statusBanner = computed(() => {
  const status = form.value.status_conta;
  if (status === 'active') {
    return {
      title: 'Aprovada e Ativa',
      description: 'A sua empresa está verificada. Já pode publicar fretes no mural operacional.',
      class: 'bg-green-50 border-green-500',
      textClass: 'text-green-800',
      icon: '✅'
    };
  } else if (status === 'rejected') {
    return {
      title: 'Documentação Recusada',
      description: 'Houve uma inconsistência nos seus dados. Por favor, reenvie a documentação correta.',
      class: 'bg-red-50 border-red-500',
      textClass: 'text-red-800',
      icon: '❌'
    };
  }
  return {
    title: 'Em Análise (Pendente)',
    description: 'Pode preencher o rascunho de fretes, mas a publicação está bloqueada até à validação documental.',
    class: 'bg-amber-50 border-amber-500',
    textClass: 'text-amber-800',
    icon: '⏳'
  };
});

const handleDocumentUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      alert('O arquivo é muito grande. O limite é 5MB.');
      event.target.value = '';
      return;
    }
    arquivoDocumento.value = file;
  }
};

const fetchPerfil = async () => {
  try {
    const response = await axios.get('/api/v1/embarcador/perfil');
    form.value = { ...form.value, ...response.data };
  } catch (error) {
    console.error('Erro ao buscar perfil:', error);
    alert('Erro ao carregar dados. Tente atualizar a página.');
  } finally {
    pageLoading.value = false;
  }
};

const updatePerfil = async () => {
  submitLoading.value = true;
  
  const formData = new FormData();
  formData.append('_method', 'PUT');

  Object.keys(form.value).forEach(key => {
    if (form.value[key] !== null && form.value[key] !== undefined && key !== 'documento_kyc_url') {
      formData.append(key, form.value[key]);
    }
  });

  if (arquivoDocumento.value) {
    formData.append('documento_kyc', arquivoDocumento.value);
  }

  try {
    const response = await axios.post('/api/v1/embarcador/perfil', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    alert('Informações guardadas com sucesso!');
    if (response.data.documento_kyc_url) {
      form.value.documento_kyc_url = response.data.documento_kyc_url;
    }
    
    if (response.data.status_conta) {
      form.value.status_conta = response.data.status_conta;
    }

  } catch (error) {
    console.error('Erro ao salvar:', error);
    if (error.response?.status === 422) {
      const errosDeValidacao = error.response.data.errors;
      let mensagemErro = 'Corrija os campos:\n';
      for (const campo in errosDeValidacao) {
        mensagemErro += `- ${errosDeValidacao[campo][0]}\n`;
      }
      alert(mensagemErro);
    } else {
      alert(error.response?.data?.message || 'Erro ao processar a atualização.');
    }
  } finally {
    submitLoading.value = false;
  }
};

onMounted(() => {
  fetchPerfil();
});
</script>