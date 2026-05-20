<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Ad Server & Rede de Parceiros</h2>
          <p class="text-sm text-gray-400">Motor de Tráfego: Gestão de Campanhas, Banners e Contratos.</p>
        </div>
        <button @click="abrirModalCriacao" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded shadow transition-colors">
          + Nova Campanha / Parceiro
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Campanha</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Estratégia (Modelo)</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Métricas Reais</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="parceiros.length === 0">
            <td colspan="5" class="px-6 py-10 text-center text-gray-500 font-bold">Nenhuma campanha rodando.</td>
          </tr>
          <tr v-for="item in parceiros" :key="item.id" class="hover:bg-gray-50" :class="{'opacity-50': !item.is_active}">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div v-if="item.imagem_url" class="flex-shrink-0 h-10 w-10 mr-4">
                  <img class="h-10 w-10 rounded object-cover border border-gray-200" :src="item.imagem_url" alt="" />
                </div>
                <div v-else class="flex-shrink-0 h-10 w-10 mr-4 bg-gray-100 rounded flex items-center justify-center text-gray-400 border border-gray-200">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                  <div class="text-sm font-bold text-gray-900">{{ item.nome }}</div>
                  <div class="text-xs text-gray-500">Público: {{ item.audience }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
               <span class="px-2 py-1 text-[10px] font-black rounded uppercase bg-indigo-50 text-indigo-700 border border-indigo-100">
                 {{ item.modelo_cobranca }}
               </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-xs">
                <div v-if="item.modelo_cobranca === 'cpc'" class="font-bold text-gray-600">
                  {{ item.cliques_acumulados }} / {{ item.limite_cliques }} Cliques
                </div>
                <div v-else-if="item.modelo_cobranca === 'cpa'" class="font-bold text-gray-600">
                  {{ item.conversoes_acumuladas }} / {{ item.limite_conversoes }} Conversões
                </div>
                <div v-else-if="item.modelo_cobranca === 'assinatura'" class="font-bold text-gray-600">
                  Vence: {{ item.data_expiracao ? new Date(item.data_expiracao).toLocaleDateString() : 'N/A' }}
                </div>
                <div v-else class="font-bold text-gray-400">- Infinito -</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
               <span class="px-2 py-1 text-xs font-bold rounded-md uppercase" 
                     :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ item.is_active ? 'Rodando' : 'Pausado' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
               <button @click="abrirModalEdicao(item)" class="px-3 py-1 bg-gray-200 text-gray-800 hover:bg-gray-300 text-xs font-bold rounded shadow-sm transition-colors">Editar</button>
               <button @click="excluirParceiro(item.id)" class="px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 text-xs font-bold rounded shadow-sm transition-colors">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="fecharModal"></div>
      
      <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-200">
        <div class="bg-gray-900 px-6 py-4 text-white">
          <h3 class="text-lg font-black uppercase tracking-tight">{{ isEditing ? 'Ajustar Campanha' : 'Nova Campanha de Tráfego' }}</h3>
        </div>

        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto custom-scrollbar">
          
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Título da Campanha / Empresa</label>
              <input v-model="form.nome" type="text" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border">
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipo de Veiculação</label>
              <select v-model="form.categoria" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border bg-white">
                <option value="propaganda">Propaganda / Banner</option>
                <option value="anuncio">Anúncio de Serviço</option>
                <option value="produto">Produto (Loja Integrada)</option>
                <option value="contrato_comercial">Contrato B2B</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Público-Alvo</label>
              <select v-model="form.audience" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border bg-white">
                <option value="todos">Todos (Plataforma Global)</option>
                <option value="motorista">Apenas App Motoristas</option>
                <option value="embarcador">Apenas Painel Embarcadores</option>
              </select>
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descrição Breve (Call to Action)</label>
              <textarea v-model="form.descricao" rows="2" class="w-full border-gray-300 rounded text-sm p-2 border resize-none"></textarea>
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">URL da Mídia (Banner)</label>
              <input v-model="form.imagem_url" type="url" class="w-full border-gray-300 rounded text-sm p-2 border">
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">URL de Destino (Tracking Link)</label>
              <input v-model="form.link_url" type="url" class="w-full border-gray-300 rounded text-sm p-2 border">
            </div>

            <div class="col-span-2 mt-4 pb-2 border-b border-gray-200">
              <h4 class="text-sm font-black text-gray-800 uppercase tracking-widest">Setup de Regras (AdTech)</h4>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Posicionamento</label>
              <select v-model="form.posicionamento" class="w-full border-gray-300 rounded text-sm p-2 border bg-white">
                <option value="topo">Header (Destaque Principal)</option>
                <option value="lateral">Sidebar (Lateral)</option>
                <option value="rodape">Footer (Rodapé)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Status do Pagamento</label>
              <select v-model="form.status_financeiro" class="w-full border-gray-300 rounded text-sm p-2 border bg-white">
                <option value="pendente">Pendente (Bloqueado)</option>
                <option value="pago">Pago (Liberar Veiculação)</option>
                <option value="isento">Isento / Bonificado</option>
              </select>
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Modelo de Contratação (Estratégia)</label>
              <select v-model="form.modelo_cobranca" class="w-full border-gray-300 rounded text-sm p-2 border bg-indigo-50 text-indigo-900 font-bold border-indigo-200">
                <option value="assinatura">Tempo de Tela (Assinatura Mensal/Quinzenal)</option>
                <option value="cpc">CPC (Comprar Pacote de Cliques)</option>
                <option value="cpa">CPA (Comprar Pacote de Conversões/Leads)</option>
                <option value="gratuito">Gratuito (Sem expiração lógica)</option>
              </select>
            </div>

            <div v-if="form.modelo_cobranca === 'assinatura'" class="col-span-2 bg-blue-50 p-4 border border-blue-200 rounded-lg animate-fade-in">
              <label class="block text-xs font-black text-blue-800 uppercase mb-1">Dias Contratados</label>
              <p class="text-[10px] text-blue-600 mb-2">A campanha sairá do ar automaticamente à meia-noite do último dia.</p>
              <input v-model.number="form.dias_duracao" type="number" min="1" class="w-full border-blue-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border bg-white">
            </div>

            <div v-if="form.modelo_cobranca === 'cpc'" class="col-span-2 bg-purple-50 p-4 border border-purple-200 rounded-lg animate-fade-in">
              <label class="block text-xs font-black text-purple-800 uppercase mb-1">Quórum de Cliques (Limite)</label>
              <p class="text-[10px] text-purple-600 mb-2">A campanha sairá do ar quando os usuários atingirem este número de cliques no banner.</p>
              <input v-model.number="form.limite_cliques" type="number" min="1" class="w-full border-purple-300 rounded focus:ring-purple-500 focus:border-purple-500 text-sm p-2 border bg-white">
            </div>

            <div v-if="form.modelo_cobranca === 'cpa'" class="col-span-2 bg-green-50 p-4 border border-green-200 rounded-lg animate-fade-in">
              <label class="block text-xs font-black text-green-800 uppercase mb-1">Quórum de Conversões (Ações)</label>
              <p class="text-[10px] text-green-600 mb-2">A campanha sairá do ar quando gerar o volume de ações pagas (ex: downloads/contratos).</p>
              <input v-model.number="form.limite_conversoes" type="number" min="1" class="w-full border-green-300 rounded focus:ring-green-500 focus:border-green-500 text-sm p-2 border bg-white">
            </div>

            <div class="col-span-2 mt-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Prioridade de Leilão (Ordem)</label>
              <input v-model.number="form.ordem_exibicao" type="number" class="w-full border-gray-300 rounded text-sm p-2 border">
            </div>

            <div class="col-span-2 flex items-center mt-2">
              <input v-model="form.is_active" id="is_active" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
              <label for="is_active" class="ml-2 block text-sm text-gray-900 font-bold">Campanha Ligar/Desligar (Substitui motor automático)</label>
            </div>

          </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-end items-center border-t border-gray-200 gap-3">
          <button @click="fecharModal" class="text-sm font-bold text-gray-600 hover:text-gray-900 px-4 py-2 border border-gray-300 bg-white rounded shadow-sm">Cancelar</button>
          <button @click="salvar" :disabled="isSaving" class="bg-blue-600 hover:bg-blue-700 text-white font-black py-2 px-6 rounded shadow transition-colors disabled:opacity-50">
            {{ isSaving ? 'Processando...' : 'Lançar Campanha' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const parceiros = ref([]);
const loading = ref(true);
const isSaving = ref(false);
const showModal = ref(false);
const isEditing = ref(false);

const baseForm = {
  id: null, nome: '', categoria: 'propaganda', audience: 'todos', descricao: '', 
  imagem_url: '', link_url: '', conteudo_contrato: '', is_active: true, ordem_exibicao: 1,
  modelo_cobranca: 'assinatura', posicionamento: 'topo', status_financeiro: 'pendente',
  dias_duracao: 15, limite_cliques: null, limite_conversoes: null
};

const form = ref({ ...baseForm });

const carregarParceiros = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/admin/crm/parceiros');
    parceiros.value = res.data;
  } catch (error) {
    console.error('Erro ao carregar parceiros:', error);
  } finally {
    loading.value = false;
  }
};

const abrirModalCriacao = () => {
  isEditing.value = false;
  form.value = { ...baseForm };
  showModal.value = true;
};

const abrirModalEdicao = (item) => {
  isEditing.value = true;
  form.value = { ...baseForm, ...item };
  showModal.value = true;
};

const fecharModal = () => {
  showModal.value = false;
};

const salvar = async () => {
  if (!form.value.nome) return alert("O título é obrigatório.");
  
  isSaving.value = true;
  try {
    // Sanitização antes do envio para evitar erros do Laravel 
    if (form.value.modelo_cobranca !== 'assinatura') form.value.dias_duracao = null;
    if (form.value.modelo_cobranca !== 'cpc') form.value.limite_cliques = null;
    if (form.value.modelo_cobranca !== 'cpa') form.value.limite_conversoes = null;

    if (isEditing.value) {
      await axios.put(`/api/v1/admin/crm/parceiros/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/v1/admin/crm/parceiros', form.value);
    }
    fecharModal();
    carregarParceiros();
  } catch (error) {
    if (error.response?.status === 422) {
      const firstError = Object.values(error.response.data.errors)[0][0];
      alert(`Validação Rejeitada: ${firstError}`);
    } else {
      alert('Falha crítica na comunicação com o servidor AdTech.');
    }
  } finally {
    isSaving.value = false;
  }
};

const excluirParceiro = async (id) => {
  if (!confirm('Deseja realmente desligar e apagar este anúncio permanentemente?')) return;
  try {
    await axios.delete(`/api/v1/admin/crm/parceiros/${id}`);
    carregarParceiros();
  } catch (error) {
    alert('Erro ao excluir parceiro.');
  }
};

onMounted(carregarParceiros);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>