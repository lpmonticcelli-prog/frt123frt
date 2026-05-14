<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Base de Embarcadores</h2>
          <p class="text-sm text-gray-400">Gestão e controle de indústrias e transportadoras parceiras.</p>
        </div>
      </div>

      <div class="p-4 bg-gray-50 border-b border-gray-200">
        <div class="relative max-w-2xl">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          </div>
          <input 
            v-model="searchQuery" 
            type="text" 
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-medium transition-all shadow-sm" 
            placeholder="Busca inteligente por Razão Social, CNPJ, Nome, E-mail ou Telefone..."
          >
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Razão Social / CNPJ</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contato (Responsável)</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="filteredEmbarcadores.length === 0">
            <td colspan="4" class="px-6 py-10 text-center text-gray-500 font-bold">Nenhum embarcador encontrado com estes filtros.</td>
          </tr>
          <tr v-for="user in filteredEmbarcadores" :key="user.id" class="hover:bg-blue-50/50 transition-colors cursor-pointer" :class="{'opacity-50 bg-red-50': user.status === 'banned'}">
            
            <td class="px-6 py-4 whitespace-nowrap" @click="abrirDetalhes(user)">
              <div class="text-sm font-bold text-gray-900">{{ user.embarcador?.razao_social || 'Não preenchido' }}</div>
              <div class="text-xs text-gray-500 font-mono mt-0.5">CNPJ: {{ user.embarcador?.cnpj || 'Não preenchido' }}</div>
              <div class="mt-1 flex gap-2">
                <span v-if="user.embarcador?.taxa_frete_percentual" class="text-[9px] bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-100 font-bold">TAXA: {{ user.embarcador.taxa_frete_percentual }}%</span>
                <span v-if="user.embarcador?.mensalidade_fixa" class="text-[9px] bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded border border-emerald-100 font-bold">SaaS: {{ formatarMoeda(user.embarcador.mensalidade_fixa) }}</span>
              </div>
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap" @click="abrirDetalhes(user)">
               <div class="text-sm text-gray-900 font-medium">{{ user.name }}</div>
               <div class="text-xs text-gray-500 mt-0.5">{{ user.email }} • {{ user.phone }}</div>
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap text-center" @click="abrirDetalhes(user)">
              <span :class="getStatusBadgeClass(user.status)">{{ user.status.toUpperCase() }}</span>
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
              <button @click.stop="abrirDetalhes(user)" class="px-3 py-1 bg-white border border-gray-300 text-gray-700 font-bold rounded shadow-sm hover:bg-gray-50 text-xs transition-colors">Dossiê</button>
              <button @click.stop="abrirModalContrato(user)" class="px-3 py-1 bg-slate-900 text-white font-bold rounded shadow hover:bg-slate-800 text-xs transition-colors">Contrato</button>
              <button v-if="user.status !== 'banned'" @click.stop="alterarStatus(user, 'banned')" class="px-3 py-1 bg-red-600 text-white font-bold rounded shadow hover:bg-red-700 text-xs transition-colors">Banir</button>
              <button v-if="user.status === 'banned'" @click.stop="alterarStatus(user, 'active')" class="px-3 py-1 bg-gray-200 text-gray-800 font-bold rounded shadow hover:bg-gray-300 text-xs transition-colors">Restaurar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModalDetalhes" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="fecharDetalhes"></div>
      
      <div class="relative bg-white rounded-xl shadow-2xl max-w-3xl w-full overflow-hidden border border-gray-200">
        <div class="bg-gray-900 px-6 py-4 text-white flex justify-between items-center">
          <div>
            <h3 class="text-lg font-black uppercase tracking-tight">Dossiê Completo do Embarcador</h3>
            <p class="text-xs text-gray-400 mt-1">ID Cadastro: #{{ userSelecionado?.id }}</p>
          </div>
          <button @click="fecharDetalhes" class="text-gray-400 hover:text-white font-bold text-2xl">&times;</button>
        </div>

        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
          
          <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg border border-gray-200">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-black text-xl">
                {{ userSelecionado?.name?.charAt(0).toUpperCase() }}
              </div>
              <div>
                <h4 class="font-black text-gray-900 text-lg">{{ userSelecionado?.name }}</h4>
                <p class="text-xs text-gray-500 font-mono">{{ userSelecionado?.email }}</p>
              </div>
            </div>
            <div class="text-right">
              <span :class="getStatusBadgeClass(userSelecionado?.status)" class="text-sm px-3 py-1">{{ userSelecionado?.status.toUpperCase() }}</span>
              <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold">Membro desde {{ new Date(userSelecionado?.created_at).toLocaleDateString('pt-BR') }}</p>
            </div>
          </div>

          <div>
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b pb-2">Informações da Indústria</h4>
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-white p-3 border border-gray-100 rounded shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase">Razão Social</span>
                <span class="text-sm font-bold text-gray-900">{{ userSelecionado?.embarcador?.razao_social || 'N/A' }}</span>
              </div>
              <div class="bg-white p-3 border border-gray-100 rounded shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase">CNPJ</span>
                <span class="text-sm font-bold text-gray-900 font-mono">{{ userSelecionado?.embarcador?.cnpj || 'N/A' }}</span>
              </div>
              <div class="bg-white p-3 border border-gray-100 rounded shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase">Inscrição Estadual</span>
                <span class="text-sm font-bold text-gray-900 font-mono">{{ userSelecionado?.embarcador?.inscricao_estadual || 'ISENTO' }}</span>
              </div>
              <div class="bg-white p-3 border border-gray-100 rounded shadow-sm">
                <span class="block text-[10px] font-bold text-gray-400 uppercase">Telefone / WhatsApp</span>
                <span class="text-sm font-bold text-gray-900 font-mono">{{ userSelecionado?.phone }}</span>
              </div>
            </div>
          </div>

          <div>
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b pb-2">Sede / Endereço Principal</h4>
            <div class="bg-white p-4 border border-gray-100 rounded shadow-sm text-sm text-gray-700">
              <template v-if="userSelecionado?.embarcador?.logradouro">
                <p><span class="font-bold">Logradouro:</span> {{ userSelecionado.embarcador.logradouro }}, {{ userSelecionado.embarcador.numero }} {{ userSelecionado.embarcador.complemento }}</p>
                <p class="mt-1"><span class="font-bold">Bairro:</span> {{ userSelecionado.embarcador.bairro }}</p>
                <p class="mt-1"><span class="font-bold">Cidade/UF:</span> {{ userSelecionado.embarcador.cidade }} / {{ userSelecionado.embarcador.uf }}</p>
                <p class="mt-1"><span class="font-bold">CEP:</span> {{ userSelecionado.embarcador.cep }}</p>
              </template>
              <template v-else>
                <p class="text-gray-400 italic">Endereço ainda não preenchido pelo cliente.</p>
              </template>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="bg-blue-50 p-4 border border-blue-100 rounded-lg">
              <span class="block text-[10px] font-black text-blue-400 uppercase">Taxa / Comissão Negociada</span>
              <span class="text-xl font-black text-blue-900 mt-1 block">{{ userSelecionado?.embarcador?.taxa_frete_percentual ? userSelecionado.embarcador.taxa_frete_percentual + '%' : 'Padrão (Global)' }}</span>
            </div>
            <div class="bg-emerald-50 p-4 border border-emerald-100 rounded-lg">
              <span class="block text-[10px] font-black text-emerald-400 uppercase">Mensalidade SaaS</span>
              <span class="text-xl font-black text-emerald-900 mt-1 block">{{ userSelecionado?.embarcador?.mensalidade_fixa ? formatarMoeda(userSelecionado.embarcador.mensalidade_fixa) : 'Isento (R$ 0,00)' }}</span>
            </div>
          </div>

        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-200">
          <button @click="fecharDetalhes" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-6 rounded shadow-sm transition-colors">
            Fechar Dossiê
          </button>
        </div>
      </div>
    </div>

    <div v-if="showModalContrato" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="fecharModalContrato"></div>
      
      <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-200">
        <div class="bg-gray-900 px-6 py-4 text-white">
          <h3 class="text-lg font-black uppercase tracking-tight">Acordo Comercial Individual</h3>
          <p class="text-xs text-gray-400 mt-1">Defina as taxas para: {{ userSelecionado?.embarcador?.razao_social }}</p>
        </div>

        <div class="p-6 space-y-6">
          <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
            <label class="block text-xs font-black text-blue-900 uppercase mb-2">Comissão por Frete (%)</label>
            <input 
              v-model.number="formContrato.taxa_frete_percentual" 
              type="number" step="0.01" 
              placeholder="Padrão: 5.00"
              class="w-full bg-white border border-blue-200 rounded p-3 text-xl font-black text-blue-900 focus:ring-2 focus:ring-blue-500 outline-none"
            >
            <p class="text-[10px] text-blue-600 mt-2 font-medium italic">Deixe em branco ou 0 para cobrar apenas a mensalidade fixa.</p>
          </div>

          <div class="bg-emerald-50 p-4 rounded-lg border border-emerald-100">
            <label class="block text-xs font-black text-emerald-900 uppercase mb-2">Mensalidade SaaS Fixa (R$)</label>
            <input 
              v-model.number="formContrato.mensalidade_fixa" 
              type="number" step="0.01" 
              placeholder="0,00"
              class="w-full bg-white border border-emerald-200 rounded p-3 text-xl font-black text-emerald-900 focus:ring-2 focus:ring-emerald-500 outline-none"
            >
            <p class="text-[10px] text-emerald-600 mt-2 font-medium italic">Valor cobrado mensalmente, independente do volume de fretes.</p>
          </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
          <button @click="fecharModalContrato" class="text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
          <button @click="salvarContrato" :disabled="isSaving" class="bg-blue-600 hover:bg-blue-700 text-white font-black py-2 px-6 rounded shadow transition-colors disabled:opacity-50">
            {{ isSaving ? 'Salvando...' : 'Aplicar Contrato' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const embarcadores = ref([]);
const loading = ref(true);
const searchQuery = ref('');

// Estados dos Modais
const showModalDetalhes = ref(false);
const showModalContrato = ref(false);
const isSaving = ref(false);
const userSelecionado = ref(null);

const formContrato = ref({
    taxa_frete_percentual: null,
    mensalidade_fixa: null
});

// Filtro Inteligente (Smart Search)
const filteredEmbarcadores = computed(() => {
  if (!searchQuery.value) return embarcadores.value;
  
  const query = searchQuery.value.toLowerCase();
  
  return embarcadores.value.filter(user => {
    return (
      user.name?.toLowerCase().includes(query) ||
      user.email?.toLowerCase().includes(query) ||
      user.phone?.includes(query) ||
      user.embarcador?.razao_social?.toLowerCase().includes(query) ||
      user.embarcador?.cnpj?.includes(query) ||
      user.status?.toLowerCase().includes(query)
    );
  });
});

const formatarMoeda = (valor) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);

const getStatusBadgeClass = (status) => {
  const map = {
    active: 'bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold border border-green-200',
    pending: 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold border border-yellow-200',
    banned: 'bg-red-600 text-white px-2 py-1 rounded text-xs font-bold shadow-sm',
    em_analise: 'bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold border border-blue-200'
  };
  return map[status] || 'bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-bold';
};

const carregarEmbarcadores = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/crm/embarcadores');
    embarcadores.value = res.data.data ? res.data.data : res.data;
  } catch (error) {
    console.error('Erro ao carregar embarcadores:', error);
  } finally {
    loading.value = false;
  }
};

// Dossiê Completo
const abrirDetalhes = (user) => {
  userSelecionado.value = user;
  showModalDetalhes.value = true;
};

const fecharDetalhes = () => {
  showModalDetalhes.value = false;
  if (!showModalContrato.value) userSelecionado.value = null;
};

// Gestão de Contrato
const abrirModalContrato = (user) => {
    userSelecionado.value = user;
    formContrato.value = {
        taxa_frete_percentual: user.embarcador?.taxa_frete_percentual,
        mensalidade_fixa: user.embarcador?.mensalidade_fixa
    };
    showModalContrato.value = true;
};

const fecharModalContrato = () => {
    showModalContrato.value = false;
    if (!showModalDetalhes.value) userSelecionado.value = null;
};

const salvarContrato = async () => {
    isSaving.value = true;
    try {
        await axios.put(`/api/admin/config/crm/embarcadores/${userSelecionado.value.embarcador.id}/contrato`, formContrato.value);
        alert('Contrato atualizado! As novas taxas serão aplicadas às próximas cargas deste cliente.');
        fecharModalContrato();
        carregarEmbarcadores(); 
    } catch (error) {
        alert('Erro ao salvar contrato. Verifique as permissões de Admin Root.');
    } finally {
        isSaving.value = false;
    }
};

const alterarStatus = async (user, status) => {
  if (!confirm(`Tem certeza que deseja ${status === 'banned' ? 'BANIR' : 'RESTAURAR'} o acesso desta indústria/transportadora?`)) return;
  
  try {
    await axios.post(`/api/admin/usuarios/${user.id}/status`, { status });
    await carregarEmbarcadores(); 
  } catch (error) {
    alert('Falha ao processar o comando de segurança.');
  }
};

onMounted(() => {
  carregarEmbarcadores();
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>