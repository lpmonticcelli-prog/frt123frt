<template>
  <div class="space-y-6 max-w-7xl mx-auto animate-fade-in">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">API Gateway B2B</h2>
          <p class="text-sm text-gray-400">Gestão de credenciais, chaves de acesso (M2M) e controle de escopo para parceiros integrados.</p>
        </div>
        <div>
          <button 
            @click="abrirModalCriar" 
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow transition-colors flex items-center space-x-2"
          >
            <span>➕ Nova Chave de Integração</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="tokenGerado" class="bg-red-50 border-2 border-red-500 rounded-xl p-6 shadow-md animate-pulse">
      <div class="flex items-start space-x-4">
        <div class="p-2 bg-red-600 text-white rounded-lg text-lg font-bold">⚠️</div>
        <div class="flex-1">
          <h3 class="text-red-900 font-black text-base uppercase tracking-wider">Chave de Acesso Gerada com Sucesso!</h3>
          <p class="text-sm text-red-700 mt-1 font-medium">
            Copie o token abaixo imediatamente. Por motivos de segurança e criptografia estrita, **este hash não será exibido novamente**. Se perder esta chave, terá de revogar o acesso e gerar uma nova.
          </p>
          
          <div class="mt-4 flex items-center space-x-2 bg-white border border-red-300 rounded p-3 font-mono text-xs text-red-600 break-all select-all relative group">
            <span class="flex-1 font-bold">{{ tokenGerado }}</span>
            <button 
              @click="copiarToken" 
              class="ml-3 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-[10px] font-black uppercase tracking-widest transition-colors shadow-sm"
            >
              {{ copiado ? 'Copiado! ✓' : 'Copiar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <div v-else-if="parceiros.length === 0" class="p-12 text-center text-gray-500 text-sm font-bold uppercase tracking-widest">
        Nenhuma chave de integração B2B ativa no sistema.
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Identificação / Canal</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Utilizador Vinculado</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Escopo de Segurança</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Criado Em</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Último Uso</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="token in parceiros" :key="token.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-black text-gray-900">{{ token.name.replace('API - ', '') }}</div>
              <div class="text-[10px] text-gray-400 font-mono">ID: #{{ token.id }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
              <span class="font-bold block text-gray-900">{{ token.tokenable?.name || 'N/A' }}</span>
              {{ token.tokenable?.email || 'N/A' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getScopeBadgeClass(token.abilities[0])">
                {{ token.abilities[0]?.toUpperCase() }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">
              {{ new Date(token.created_at).toLocaleDateString('pt-BR') }} <br>
              <span class="text-[10px] text-gray-400">{{ new Date(token.created_at).toLocaleTimeString('pt-BR') }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">
              <span v-if="token.last_used_at" class="text-gray-900 font-bold">
                {{ new Date(token.last_used_at).toLocaleDateString('pt-BR') }}
              </span>
              <span v-else class="text-gray-400 italic">Nunca Utilizado</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button 
                @click="revogarAcesso(token)" 
                class="px-3 py-1 bg-red-100 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 rounded text-xs font-bold transition-all"
              >
                Revogar Acesso (Kill Switch)
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="modalAberto" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-sm">
      <div class="bg-white w-full max-w-md rounded-xl shadow-2xl overflow-hidden border border-gray-200 animate-fade-in">
        <div class="px-6 py-4 bg-gray-900 text-white flex justify-between items-center">
          <h3 class="text-base font-black uppercase tracking-wider">Nova Credencial B2B</h3>
          <button @click="fecharModal" class="text-gray-400 hover:text-white text-xl font-bold">&times;</button>
        </div>
        
        <form @submit.prevent="submeterFormulario" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nome do Parceiro / Empresa</label>
            <input 
              v-model="form.nome_parceiro" 
              type="text" 
              placeholder="Ex: e3 Seguros - Corporativo" 
              required
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-medium"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">E-mail de Contato Técnico</label>
            <input 
              v-model="form.email_contato" 
              type="email" 
              placeholder="Ex: api-b2b@e3seguros.com.br" 
              required
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-medium"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tipo de Acesso (Escopo / Scope)</label>
            <select 
              v-model="form.tipo_acesso" 
              required
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-bold text-gray-800 bg-white"
            >
              <option value="" disabled selected>Selecione o nível de privilégio...</option>
              <option value="gr-partner">🛡️ Gerenciadora de Risco (GR - Callback Analise)</option>
              <option value="seguradora-partner">📜 Seguradora (Averbações de Apólice)</option>
              <option value="erp-partner">🏢 ERP Embarcador (Injeção de Cargas Direta)</option>
              <option value="gateway-partner">💳 Gateway PEF (Emissão de CIOT/Pagamentos)</option>
            </select>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end space-x-2">
            <button 
              type="button" 
              @click="fecharModal" 
              class="px-4 py-2 border border-gray-300 rounded text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              :disabled="submitting"
              class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow transition-colors disabled:opacity-50"
            >
              {{ submitting ? 'A Processar...' : 'Gerar Chave de Segurança' }}
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

const parceiros = ref([]);
const loading = ref(true);
const submitting = ref(false);
const modalAberto = ref(false);
const tokenGerado = ref(null);
const copiado = ref(false);

const form = ref({
  nome_parceiro: '',
  email_contato: '',
  tipo_acesso: ''
});

const getScopeBadgeClass = (scope) => {
  const base = "px-2.5 py-1 text-[10px] font-black rounded-md uppercase tracking-wider border ";
  const styles = {
    'gr-partner': 'bg-blue-50 text-blue-800 border-blue-200',
    'seguradora-partner': 'bg-purple-50 text-purple-800 border-purple-200',
    'erp-partner': 'bg-indigo-50 text-indigo-800 border-indigo-200',
    'gateway-partner': 'bg-green-50 text-green-800 border-green-200'
  };
  return styles[scope] || base + 'bg-gray-50 text-gray-800 border-gray-200';
};

const buscarParceiros = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/parceiros-api');
    parceiros.value = response.data;
  } catch (error) {
    console.error('Erro ao carregar parceiros API:', error);
    alert('Erro crítico ao ler os tokens do cofre.');
  } finally {
    loading.value = false;
  }
};

const abrirModalCriar = () => {
  form.value = { nome_parceiro: '', email_contato: '', tipo_acesso: '' };
  modalAberto.value = true;
};

const fecharModal = () => {
  modalAberto.value = false;
};

const submeterFormulario = async () => {
  submitting.value = true;
  try {
    const response = await axios.post('/api/v1/admin/parceiros-api', form.value);
    tokenGerado.value = response.data.token;
    copiado.value = false;
    fecharModal();
    await buscarParceiros();
  } catch (error) {
    console.error('Erro ao gerar credencial:', error);
    alert(error.response?.data?.message || 'Erro de comunicação ao criar chave.');
  } finally {
    submitting.value = false;
  }
};

const copiarToken = () => {
  if (!tokenGerado.value) return;
  navigator.clipboard.writeText(tokenGerado.value);
  copiado.value = true;
  setTimeout(() => {
    copiado.value = false;
  }, 3000);
};

const revogarAcesso = async (token) => {
  const nomeLimpo = token.name.replace('API - ', '');
  if (!confirm(`💣 ATENÇÃO CRÍTICA: Tem certeza de que deseja ativar o KILL SWITCH para o parceiro "${nomeLimpo}"?\n\nO acesso de qualquer máquina ou servidor usando esta chave será bloqueado imediatamente!`)) return;

  try {
    await axios.post(`/api/v1/admin/parceiros-api/${token.id}/revogar`);
    alert('Acesso revogado com sucesso! Porta trancada.');
    await buscarParceiros();
  } catch (error) {
    console.error('Erro ao revogar chave:', error);
    alert('Falha interna ao disparar o Kill Switch.');
  }
};

onMounted(() => {
  buscarParceiros();
});
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>