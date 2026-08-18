<template>
  <div class="w-full relative min-h-screen bg-slate-50 pb-12 pt-8 px-4 sm:px-6">
    <div class="max-w-5xl mx-auto space-y-8">
      
      <!-- HEADER -->
      <div class="flex flex-col md:flex-row md:items-center justify-between bg-white p-6 rounded-2xl border border-slate-200 shadow-sm gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Locais Operacionais (Docas)</h2>
          <p class="text-sm text-slate-500 mt-1 font-medium">Gira os galpões, docas de coleta e centros de distribuição da sua operação.</p>
        </div>
      </div>

      <!-- ESTADO: CARREGANDO -->
      <div v-if="pageLoading" class="flex flex-col justify-center items-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <svg class="w-10 h-10 animate-spin text-[#ff5500] mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <div class="text-slate-500 font-bold tracking-wide">Carregando infraestrutura...</div>
      </div>

      <!-- CONTEÚDO PRINCIPAL -->
      <div v-else>
        <!-- ESTADO: VAZIO -->
        <div v-if="locais.length === 0" class="bg-white rounded-2xl border border-slate-200 border-dashed p-16 text-center shadow-sm">
          <div class="mx-auto w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5 border border-slate-100 shadow-inner">
            <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-slate-900 tracking-tight">Nenhum local cadastrado</h3>
          <p class="text-slate-500 mt-2 max-w-md mx-auto">Adicione seu primeiro galpão ou endereço de coleta para começar a publicar fretes na plataforma.</p>
        </div>

        <!-- LISTAGEM DE LOCAIS -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div 
            v-for="local in locais" 
            :key="local.id" 
            class="relative bg-white rounded-2xl shadow-sm border p-6 transition-all hover:shadow-md"
            :class="local.is_padrao ? 'border-[#035D29] ring-1 ring-[#035D29]' : 'border-slate-200'"
          >
            <!-- Etiqueta Sede Padrão -->
            <div v-if="local.is_padrao" class="absolute top-0 right-0 bg-[#035D29] text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-bl-xl rounded-tr-2xl shadow-sm">
              Sede / Padrão
            </div>

            <div class="flex justify-between items-start mb-4">
              <h3 class="font-black text-slate-900 text-lg tracking-tight">{{ local.nome_identificador }}</h3>
              <button 
                v-if="!local.is_padrao" 
                @click="excluirLocal(local.id)" 
                class="text-slate-400 hover:text-red-500 transition-colors p-1"
                title="Excluir Local"
              >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>

            <div class="text-sm text-slate-600 space-y-1.5 font-medium">
              <p>{{ local.logradouro }}, {{ local.numero || 'S/N' }} {{ local.complemento ? '- ' + local.complemento : '' }}</p>
              <p>{{ local.bairro }}</p>
              <p class="font-bold text-slate-800">{{ local.cidade }} - {{ local.uf }}</p>
              <p class="font-mono text-xs text-slate-400 font-bold pt-3 border-t border-slate-100 mt-3">CEP: {{ local.cep }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- FORMULÁRIO: NOVO LOCAL -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-8">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
          <h3 class="text-lg font-black text-slate-900 tracking-tight">Adicionar Novo Local</h3>
          <p class="text-sm text-slate-500 mt-1 font-medium">Preencha os dados da nova doca ou galpão logístico.</p>
        </div>
        
        <form @submit.prevent="salvarLocal" class="p-6 sm:p-8 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
            
            <div class="md:col-span-6">
              <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Nome Identificador <span class="text-red-500">*</span></label>
              <input v-model="form.nome_identificador" type="text" required placeholder="Ex: Galpão São Paulo, Doca 04, Matriz" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">CEP <span class="text-red-500">*</span></label>
              <div class="flex shadow-sm rounded-xl">
                <input v-model="form.cep" type="text" required maxlength="9" @blur="buscarCepProxy" class="w-full px-4 py-3 border border-slate-300 rounded-l-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm font-mono bg-slate-50 focus:bg-white transition-colors" placeholder="00000-000">
                <button type="button" @click="buscarCepProxy" :disabled="buscandoCep" class="bg-slate-100 border-y border-r border-slate-300 px-5 rounded-r-xl text-xs font-bold text-slate-700 hover:bg-[#035D29] hover:text-white hover:border-[#035D29] transition-colors disabled:opacity-50 uppercase tracking-wider">
                  {{ buscandoCep ? '...' : 'Buscar' }}
                </button>
              </div>
            </div>
            
            <div class="md:col-span-4">
              <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Logradouro <span class="text-red-500">*</span></label>
              <input v-model="form.logradouro" type="text" required class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Número</label>
              <input v-model="form.numero" type="text" id="inputNumero" placeholder="S/N se vazio" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
            </div>
            
            <div class="md:col-span-4">
              <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Complemento</label>
              <input v-model="form.complemento" type="text" placeholder="Galpão B, Sala 3..." class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Bairro <span class="text-red-500">*</span></label>
              <input v-model="form.bairro" type="text" required class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
            </div>
            
            <div class="md:col-span-3">
              <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">Cidade <span class="text-red-500">*</span></label>
              <input v-model="form.cidade" type="text" required class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white transition-colors shadow-sm">
            </div>
            
            <div class="md:col-span-1">
              <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-2">UF <span class="text-red-500">*</span></label>
              <input v-model="form.uf" type="text" required maxlength="2" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#035D29] focus:border-[#035D29] text-sm bg-slate-50 focus:bg-white uppercase transition-colors shadow-sm">
            </div>
          </div>

          <div class="flex items-center pt-2">
            <input id="is_padrao" v-model="form.is_padrao" type="checkbox" class="h-5 w-5 text-[#035D29] focus:ring-[#035D29] border-slate-300 rounded cursor-pointer transition-colors">
            <label for="is_padrao" class="ml-3 block text-sm text-slate-700 font-bold cursor-pointer">
              Definir como sede / endereço operacional padrão
            </label>
          </div>

          <div class="pt-8 border-t border-slate-200 flex justify-end">
            <button 
              type="submit" 
              :disabled="submitLoading"
              class="w-full sm:w-auto px-8 py-3 bg-[#035D29] text-white font-bold rounded-xl hover:bg-[#023818] transition-colors shadow-md disabled:opacity-50"
            >
              {{ submitLoading ? 'A Registrar...' : 'Cadastrar Local Operacional' }}
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const pageLoading = ref(true);
const submitLoading = ref(false);
const buscandoCep = ref(false);
const locais = ref([]);

const getFormPadrao = () => ({
  nome_identificador: '',
  cep: '',
  logradouro: '',
  numero: '',
  complemento: '',
  bairro: '',
  cidade: '',
  uf: '',
  is_padrao: false
});

const form = ref(getFormPadrao());

const carregarLocais = async () => {
  try {
    const response = await axios.get('/api/v1/embarcador/locais');
    locais.value = response.data;
  } catch (error) {
    console.error('Erro ao buscar locais:', error);
  } finally {
    pageLoading.value = false;
  }
};

const buscarCepProxy = async () => {
  const cepLimpo = form.value.cep?.replace(/\D/g, '');
  if (!cepLimpo || cepLimpo.length !== 8) return;

  buscandoCep.value = true;
  try {
    // ZT-DEFENSE: Comunicação com o próprio backend (Proxy). Fim do problema de CORS.
    const response = await axios.get(`/api/v1/localidades/cep/${cepLimpo}`);
    
    if (response.data) {
      form.value.logradouro = response.data.logradouro;
      form.value.bairro = response.data.bairro;
      form.value.cidade = response.data.localidade;
      form.value.uf = response.data.uf;
      
      // Foca no número para facilitar a digitação do usuário
      setTimeout(() => document.getElementById('inputNumero')?.focus(), 100);
    }
  } catch (error) {
    alert(error.response?.data?.error || 'Não foi possível buscar o endereço.');
  } finally {
    buscandoCep.value = false;
  }
};

const salvarLocal = async () => {
  submitLoading.value = true;
  try {
    await axios.post('/api/v1/embarcador/locais', form.value);
    alert('Local operacional cadastrado com sucesso!');
    form.value = getFormPadrao(); // Reseta o formulário
    await carregarLocais(); // Recarrega a lista
  } catch (error) {
    if (error.response?.status === 422) {
      const errosDeValidacao = error.response.data.errors;
      let mensagemErro = 'Corrija os campos:\n';
      for (const campo in errosDeValidacao) {
        mensagemErro += `- ${errosDeValidacao[campo][0]}\n`;
      }
      alert(mensagemErro);
    } else {
      alert(error.response?.data?.error || 'Erro ao registrar local.');
    }
  } finally {
    submitLoading.value = false;
  }
};

const excluirLocal = async (id) => {
  if (!confirm('Tem a certeza que deseja excluir esta doca/galpão?')) return;
  
  try {
    await axios.delete(`/api/v1/embarcador/locais/${id}`);
    await carregarLocais();
  } catch (error) {
    alert(error.response?.data?.error || 'Falha ao excluir o local.');
  }
};

onMounted(() => {
  carregarLocais();
});
</script>