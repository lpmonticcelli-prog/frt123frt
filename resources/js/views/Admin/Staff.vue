<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Staff & Permissões</h2>
          <p class="text-sm text-gray-400">Controlo de acesso de nível administrativo da plataforma.</p>
        </div>
        <button @click="abrirModalCriacao" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded shadow transition-colors">
          + Novo Administrador / Staff
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
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Membro da Equipa</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contato</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nível de Acesso (Cargo)</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="admin in staff" :key="admin.id" class="hover:bg-gray-50" :class="{'opacity-60 bg-red-50': admin.status !== 'active'}">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-gray-900">{{ admin.name }}</div>
              <div class="text-xs text-gray-500">Adicionado a {{ new Date(admin.created_at).toLocaleDateString('pt-BR') }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
               <div class="text-sm text-gray-900">{{ admin.email }}</div>
               <div class="text-xs text-gray-500">{{ admin.phone || 'Sem telefone' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getRoleBadgeClass(admin.role?.slug)">
                {{ admin.role?.name || 'N/A' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
               <span class="px-2 py-1 text-xs font-bold rounded-md uppercase" 
                     :class="admin.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ admin.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
               <button v-if="admin.id !== authStore.user?.id" @click="abrirModalEdicao(admin)" class="px-3 py-1.5 bg-gray-200 text-gray-800 hover:bg-gray-300 text-xs font-bold rounded shadow-sm transition-colors">
                 Editar Permissão
               </button>
               <span v-else class="px-3 py-1.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">
                 Sua Conta
               </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
            <h3 class="text-lg font-black text-gray-900">{{ isEditing ? 'Editar Privilégios do Staff' : 'Adicionar Membro ao Staff' }}</h3>
            <p class="text-sm text-gray-500">Esta ação ficará registada no log de auditoria em seu nome.</p>
          </div>
          
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome Completo</label>
              <input v-model="form.name" type="text" :disabled="isEditing" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border disabled:bg-gray-100 disabled:text-gray-500" placeholder="Ex: João Silva">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">E-mail Profissional</label>
                <input v-model="form.email" type="email" :disabled="isEditing" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border disabled:bg-gray-100 disabled:text-gray-500" placeholder="joao@123fretei.com.br">
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Celular / Telefone</label>
                <input v-model="form.phone" type="text" :disabled="isEditing" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border disabled:bg-gray-100 disabled:text-gray-500" placeholder="11999999999">
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-blue-700 uppercase mb-1">Nível de Permissão (Cargo)</label>
              <select v-model="form.role_slug" class="w-full border-blue-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border bg-blue-50 font-bold">
                <option value="" disabled v-if="!isEditing">Selecione um cargo estrutural...</option>
                <option value="admin">Sócio / Super Admin (Root) - Acesso Total</option>
                <option value="manager">Gestor de Operações - Gestão de CRM e Disputas</option>
                <option value="compliance">Analista KYC - Aprovação de Documentos</option>
                <option value="suporte_n1">Suporte Operacional - Leitura e Atendimento</option>
              </select>
            </div>

            <div v-if="isEditing" class="p-4 bg-gray-50 border border-gray-200 rounded">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Controle de Segurança (Status)</label>
              <select v-model="form.status" class="w-full border-gray-300 rounded text-sm p-2 border font-bold" :class="form.status === 'active' ? 'text-green-700' : 'text-red-700'">
                <option value="active">🟢 Conta Ativa e Liberada</option>
                <option value="suspended">🟡 Suspender Acesso Temporariamente</option>
                <option value="banned">🔴 Banir Membro da Plataforma</option>
              </select>
              <p class="text-xs text-gray-500 mt-2">Suspender ou banir derrubará a sessão deste colaborador instantaneamente.</p>
            </div>

            <div v-if="!isEditing">
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Senha de Acesso Temporária</label>
              <input v-model="form.password" type="password" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm p-2 border" placeholder="Mínimo 8 caracteres">
            </div>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end gap-3 border-t border-gray-200">
            <button type="button" @click="showModal = false" class="px-4 py-2 bg-white text-gray-700 font-bold text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
              Cancelar
            </button>
            <button type="button" @click="salvarStaff" :disabled="isSaving" class="px-6 py-2 bg-blue-600 text-white font-bold text-sm rounded shadow hover:bg-blue-700 disabled:opacity-50 transition-colors">
              {{ isSaving ? 'Processando...' : (isEditing ? 'Atualizar Permissões' : 'Criar Conta') }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth'; // Importado para proteção anti-self-lockout

const authStore = useAuthStore();
const staff = ref([]);
const loading = ref(true);
const showModal = ref(false);
const isSaving = ref(false);
const isEditing = ref(false); 

const form = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  role_slug: '',
  password: '',
  status: 'active'
});

const getRoleBadgeClass = (slug) => {
  const map = {
    admin: 'bg-purple-100 text-purple-800',
    manager: 'bg-blue-100 text-blue-800',
    compliance: 'bg-yellow-100 text-yellow-800',
    suporte_n1: 'bg-gray-100 text-gray-800'
  };
  return `px-2 py-1 text-xs font-bold rounded-md uppercase ${map[slug] || 'bg-gray-100 text-gray-800'}`;
};

const carregarStaff = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/config/staff');
    staff.value = res.data.data ? res.data.data : res.data; // Compatibilidade com paginação
  } catch (error) {
    console.error('Erro ao carregar staff:', error);
  } finally {
    loading.value = false;
  }
};

const abrirModalCriacao = () => {
  isEditing.value = false;
  form.value = { id: null, name: '', email: '', phone: '', role_slug: '', password: '', status: 'active' };
  showModal.value = true;
};

const abrirModalEdicao = (adminUser) => {
  isEditing.value = true;
  form.value = { 
    id: adminUser.id, 
    name: adminUser.name, 
    email: adminUser.email, 
    phone: adminUser.phone, 
    role_slug: adminUser.role?.slug || '', 
    password: '',
    status: adminUser.status || 'active'
  };
  showModal.value = true;
};

const salvarStaff = async () => {
  isSaving.value = true;
  try {
    if (isEditing.value) {
      if (!form.value.role_slug || !form.value.status) {
        alert("Preencha o cargo e o status.");
        isSaving.value = false;
        return;
      }
      const res = await axios.put(`/api/admin/config/staff/${form.value.id}`, {
        role_slug: form.value.role_slug,
        status: form.value.status
      });
      alert(res.data.message);
    } else {
      if (!form.value.name || !form.value.email || !form.value.role_slug || !form.value.password) {
        alert("Preencha todos os campos obrigatórios.");
        isSaving.value = false;
        return;
      }
      const res = await axios.post('/api/admin/config/staff', form.value);
      alert(res.data.message);
    }
    
    showModal.value = false;
    carregarStaff();
  } catch (error) {
    const msg = error.response?.data?.message || 'Erro ao processar a requisição.';
    alert(msg);
  } finally {
    isSaving.value = false;
  }
};

onMounted(carregarStaff);
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>