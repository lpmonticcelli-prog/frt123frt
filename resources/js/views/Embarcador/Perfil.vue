<template>
  <div class="w-full relative min-h-screen bg-slate-50 pb-12 pt-8 px-4 sm:px-6">
    <div class="max-w-4xl mx-auto space-y-6">
      
      <!-- HEADER -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Configurações e Compliance</h2>
          <p class="text-sm text-slate-500 mt-1 font-medium">Gira os dados empresariais e a conformidade (KYC) da sua indústria.</p>
        </div>
      </div>

      <!-- ESTADO: CARREGANDO -->
      <div v-if="pageLoading" class="flex flex-col justify-center items-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <svg class="w-10 h-10 animate-spin text-[#ff5500] mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <div class="text-slate-500 font-bold tracking-wide">A carregar informações seguras da conta...</div>
      </div>

      <div v-else class="space-y-6">
        
        <!-- BANNER DE STATUS KYC -->
        <div :class="statusBanner.bg" class="border-l-4 p-6 rounded-r-2xl shadow-sm flex items-start">
          <div class="flex-shrink-0 mt-0.5">
            <svg class="w-8 h-8" :class="statusBanner.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusBanner.svg"></path>
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-base font-black uppercase tracking-widest" :class="statusBanner.text">
              Status Operacional: {{ statusBanner.title }}
            </h3>
            <p class="text-sm mt-1.5 font-medium leading-relaxed" :class="statusBanner.text">
              {{ statusBanner.description }}
            </p>
          </div>
        </div>

        <!-- FORMULÁRIO PRINCIPAL -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
          <form @submit.prevent="updatePerfil" class="p-6 sm:p-8 space-y-10">
            
            <!-- DADOS EMPRESARIAIS -->
            <div>
              <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 pb-3">Dados Empresariais e Fiscais</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Razão Social <span class="text-red-500">*</span></label>
                  <input v-model="form.razao_social" type="text" required class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
                </div>
                <div>
                  <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">CNPJ <span class="text-red-500">*</span></label>
                  <input v-model="form.cnpj" type="text" required maxlength="18" placeholder="00.000.000/0000-00" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors font-mono shadow-sm">
                </div>
                <div>
                  <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Inscrição Estadual (IE)</label>
                  <input v-model="form.inscricao_estadual" type="text" placeholder="ISENTO ou Número" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors font-mono shadow-sm">
                </div>
                <div class="md:col-span-2">
                  <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Telefone / WhatsApp Comercial <span class="text-red-500">*</span></label>
                  <input v-model="form.telefone" type="text" required class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
                </div>
              </div>
            </div>

            <!-- KYC / DOCUMENTAÇÃO -->
            <div class="bg-slate-50/50 p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-inner">
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-3">Validação de Conta (KYC)</h3>
              <p class="text-sm text-slate-600 mb-6 font-medium leading-relaxed">
                Para publicar fretes e transacionar na plataforma, faça o upload do Cartão CNPJ atualizado ou do Contrato Social da empresa. Nossa equipe de Compliance aprovará a sua conta em até 24 horas.
              </p>
              
              <div class="flex flex-col sm:flex-row items-center sm:space-x-6 bg-white p-5 rounded-xl border border-slate-200 shadow-sm gap-4 sm:gap-0">
                <div class="flex-1 w-full">
                  <input 
                    type="file" 
                    accept=".pdf,image/png,image/jpeg" 
                    @change="handleDocumentUpload" 
                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-black file:bg-[#035D29] file:text-white hover:file:bg-[#023818] cursor-pointer bg-slate-50 rounded-xl transition-all shadow-inner focus:outline-none"
                  />
                  <p class="text-[10px] font-black text-slate-400 mt-3 uppercase tracking-widest">Formatos: PDF, JPG ou PNG. Máx: 5MB.</p>
                </div>
                
                <div v-if="form.documento_kyc_url" class="flex-shrink-0 text-center sm:border-l border-t sm:border-t-0 border-slate-200 pt-4 sm:pt-0 sm:pl-8 w-full sm:w-auto">
                  <a :href="form.documento_kyc_url" target="_blank" class="text-xs font-bold text-[#035D29] hover:text-[#023818] flex flex-col items-center transition-colors group">
                    <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mb-2 group-hover:scale-105 transition-transform shadow-sm">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#035D29]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    Visualizar Arquivo
                  </a>
                </div>
              </div>
            </div>

            <!-- BOTÃO SALVAR -->
            <div class="pt-8 border-t border-slate-200 flex justify-end">
              <button 
                type="submit" 
                :disabled="submitLoading"
                class="w-full sm:w-auto px-10 py-3.5 bg-[#035D29] text-white font-bold rounded-xl hover:bg-[#023818] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#035D29] disabled:opacity-50 transition-colors shadow-md"
              >
                {{ submitLoading ? 'A Guardar Alterações...' : 'Salvar Informações' }}
              </button>
            </div>

          </form>
        </div>
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

// Lógica de UI para o Status (Refatorado para SVG/Premium look)
const statusBanner = computed(() => {
  const status = form.value.status_conta;
  if (status === 'active') {
    return {
      title: 'Aprovada e Ativa',
      description: 'A sua empresa está verificada. Já pode publicar fretes no mural operacional de maneira ilimitada.',
      bg: 'bg-emerald-50 border-emerald-500',
      text: 'text-emerald-800',
      iconColor: 'text-emerald-500',
      svg: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    };
  } else if (status === 'rejected') {
    return {
      title: 'Documentação Recusada',
      description: 'Houve uma inconsistência nos seus dados. Por favor, reenvie a documentação correta ou entre em contato com o suporte.',
      bg: 'bg-red-50 border-red-500',
      text: 'text-red-800',
      iconColor: 'text-red-500',
      svg: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
    };
  }
  return {
    title: 'Em Análise (Pendente)',
    description: 'Pode preencher o rascunho de fretes, mas a publicação está bloqueada até à validação documental pela nossa equipe.',
    bg: 'bg-amber-50 border-amber-500',
    text: 'text-amber-800',
    iconColor: 'text-amber-500',
    svg: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
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