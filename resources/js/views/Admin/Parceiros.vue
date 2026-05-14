<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Rede de Parceiros (CMS)</h2>
          <p class="text-sm text-gray-400">Gestão de Banners, Produtos da Loja e Contratos Comerciais.</p>
        </div>
        <button @click="abrirModalCriacao" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded shadow transition-colors">
          + Novo Parceiro / Anúncio
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
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Campanha / Produto</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Categoria</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Público-Alvo</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="parceiros.length === 0">
            <td colspan="5" class="px-6 py-10 text-center text-gray-500 font-bold">Nenhum parceiro ou anúncio configurado.</td>
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
                  <div class="text-xs text-gray-500">Ordem: {{ item.ordem_exibicao }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
               <span class="px-2 py-1 text-[10px] font-bold rounded uppercase bg-blue-50 text-blue-700 border border-blue-100">
                 {{ item.categoria.replace('_', ' ') }}
               </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
               <span class="text-xs font-black text-gray-700 uppercase">
                 {{ item.audience }}
               </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
               <span class="px-2 py-1 text-xs font-bold rounded-md uppercase" 
                     :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ item.is_active ? 'Ativo' : 'Inativo' }}
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
          <h3 class="text-lg font-black uppercase tracking-tight">{{ isEditing ? 'Editar Parceiro/Anúncio' : 'Novo Parceiro/Anúncio' }}</h3>
        </div>

        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto custom-scrollbar">
          
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Título / Nome da Empresa</label>
              <input v-model="form.nome" type="text" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border" placeholder="Ex: Pneus Michelin">
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Categoria</label>
              <select v-model="form.categoria" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border bg-white">
                <option value="propaganda">Propaganda / Banner</option>
                <option value="anuncio">Anúncio de Serviço</option>
                <option value="produto">Produto (Loja 123fretei)</option>
                <option value="contrato_comercial">Contrato Comercial</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Público-Alvo</label>
              <select v-model="form.audience" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border bg-white">
                <option value="todos">Todos (Motoristas e Embarcadores)</option>
                <option value="motorista">Apenas Motoristas</option>
                <option value="embarcador">Apenas Embarcadores</option>
              </select>
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descrição Breve</label>
              <textarea v-model="form.descricao" rows="2" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border resize-none" placeholder="Ex: Desconto de 15% na troca de pneus..."></textarea>
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">URL da Imagem / Banner</label>
              <input v-model="form.imagem_url" type="url" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border" placeholder="https://site.com/banner.jpg">
            </div>

            <div class="col-span-2">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Link de Destino (Ao Clicar)</label>
              <input v-model="form.link_url" type="url" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border" placeholder="https://parceiro.com/promocao">
            </div>

            <div v-if="form.categoria === 'contrato_comercial'" class="col-span-2 bg-yellow-50 p-4 border border-yellow-200 rounded-lg">
              <label class="block text-xs font-black text-yellow-800 uppercase mb-1">Termos do Contrato Comercial</label>
              <textarea v-model="form.conteudo_contrato" rows="4" class="w-full border-yellow-300 rounded focus:ring-yellow-500 focus:border-yellow-500 text-sm p-2 border resize-none bg-white" placeholder="Cole o texto jurídico do contrato aqui..."></textarea>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ordem de Exibição</label>
              <input v-model.number="form.ordem_exibicao" type="number" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border" placeholder="Ex: 1">
            </div>

            <div class="flex items-center mt-6">
              <input v-model="form.is_active" id="is_active" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
              <label for="is_active" class="ml-2 block text-sm text-gray-900 font-bold">Conteúdo Ativo e Visível</label>
            </div>

          </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-end items-center border-t border-gray-200 gap-3">
          <button @click="fecharModal" class="text-sm font-bold text-gray-600 hover:text-gray-900 px-4 py-2 border border-gray-300 bg-white rounded shadow-sm">Cancelar</button>
          <button @click="salvar" :disabled="isSaving" class="bg-blue-600 hover:bg-blue-700 text-white font-black py-2 px-6 rounded shadow transition-colors disabled:opacity-50">
            {{ isSaving ? 'Salvando...' : 'Guardar Conteúdo' }}
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

const form = ref({
  id: null,
  nome: '',
  categoria: 'propaganda',
  audience: 'todos',
  descricao: '',
  imagem_url: '',
  link_url: '',
  conteudo_contrato: '',
  is_active: true,
  ordem_exibicao: 0
});

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
  form.value = { id: null, nome: '', categoria: 'propaganda', audience: 'todos', descricao: '', imagem_url: '', link_url: '', conteudo_contrato: '', is_active: true, ordem_exibicao: 0 };
  showModal.value = true;
};

const abrirModalEdicao = (item) => {
  isEditing.value = true;
  form.value = { ...item };
  showModal.value = true;
};

const fecharModal = () => {
  showModal.value = false;
};

const salvar = async () => {
  if (!form.value.nome) {
    alert("O nome é obrigatório.");
    return;
  }
  
  isSaving.value = true;
  try {
    if (isEditing.value) {
      await axios.put(`/api/v1/admin/crm/parceiros/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/v1/admin/crm/parceiros', form.value);
    }
    fecharModal();
    carregarParceiros();
  } catch (error) {
    alert('Erro ao salvar. Verifique se preencheu tudo corretamente.');
  } finally {
    isSaving.value = false;
  }
};

const excluirParceiro = async (id) => {
  if (!confirm('Deseja realmente apagar este anúncio/parceiro permanentemente?')) return;
  
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