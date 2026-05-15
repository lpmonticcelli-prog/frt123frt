<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Centro de Comando</h2>
          <p class="text-sm text-gray-400">Monitoramento global da plataforma 123fretei.</p>
        </div>
        <div class="flex space-x-2 overflow-x-auto pb-2 md:pb-0">
          <button @click="mudarAba('overview')" :class="tabClass('overview')">Visão Geral</button>
          <button @click="mudarAba('kyc')" :class="tabClass('kyc')">Auditoria KYC <span v-if="kycQueue?.length" class="ml-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ kycQueue?.length }}</span></button>
          <button @click="mudarAba('crm')" :class="tabClass('crm')">Base de Usuários</button>
          <button @click="mudarAba('finance')" :class="tabClass('finance')">Operações & Fretes</button>
        </div>
      </div>
    </div>

    <div v-if="loadingGlobal" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else>
      
      <div v-if="activeTab === 'overview'" class="space-y-6 animate-fade-in">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-6 shadow-lg border border-gray-700 text-white">
            <h3 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Receita (Taxas Retidas)</h3>
            <p class="text-3xl font-black mt-2 text-green-400">{{ formatMoney(stats.receita_plataforma) }}</p>
            <p class="text-xs text-gray-400 mt-2">Transacionado: {{ formatMoney(stats.volume_transacionado) }}</p>
          </div>
          <div @click="irParaAuditoria" class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 cursor-pointer hover:bg-blue-50 transition-colors group">
            <div class="flex justify-between items-start">
               <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Fretes Concluídos</h3>
               <span class="opacity-0 group-hover:opacity-100 text-blue-600 text-xs font-black uppercase tracking-widest transition-opacity">Ver ➔</span>
            </div>
            <p class="text-3xl font-black mt-2 text-gray-900">{{ stats.fretes_concluidos }}</p>
            <p class="text-xs text-blue-600 font-bold mt-2">+ {{ stats.fretes_ativos }} em andamento</p>
          </div>
          <div @click="irParaCrm('motorista')" class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors group">
            <div class="flex justify-between items-start">
              <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Motoristas Ativos</h3>
              <span class="opacity-0 group-hover:opacity-100 text-gray-400 text-xs font-black uppercase tracking-widest transition-opacity">Filtrar ➔</span>
            </div>
            <p class="text-3xl font-black mt-2 text-gray-900">{{ stats.motoristas_ativos }}</p>
            <p class="text-xs text-gray-400 mt-2">Prontos para operar</p>
          </div>
          <div @click="irParaCrm('embarcador')" class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors group">
            <div class="flex justify-between items-start">
               <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Indústrias Ativas</h3>
               <span class="opacity-0 group-hover:opacity-100 text-gray-400 text-xs font-black uppercase tracking-widest transition-opacity">Filtrar ➔</span>
            </div>
            <p class="text-3xl font-black mt-2 text-gray-900">{{ stats.embarcadores_ativos }}</p>
            <p class="text-xs text-gray-400 mt-2">Publicando fretes</p>
          </div>
        </div>
      </div>

      <div v-if="activeTab === 'kyc'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
        <div v-if="kycQueue?.length === 0" class="p-12 text-center">
          <h3 class="mt-4 text-lg font-bold text-gray-900">Fila Limpa</h3>
          <p class="mt-1 text-sm text-gray-500">Não há nenhum usuário aguardando análise de documentos no momento.</p>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Data/Hora</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Usuário / Contato</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tipo (Role)</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Ação</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in kycQueue" :key="user.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ new Date(user.created_at).toLocaleDateString('pt-BR') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                <div class="text-xs text-gray-500">{{ user.email }} • {{ user.phone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'px-2 py-1 text-xs font-bold rounded-md uppercase',
                  user.role?.slug === 'embarcador' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                ]">
                  {{ user.role?.name }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-xs font-bold bg-yellow-100 text-yellow-800 px-2 py-1 rounded-md uppercase">
                  {{ user.status?.replace('_', ' ') }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <button @click="abrirKycModal(user)" class="px-4 py-2 bg-gray-800 text-white text-xs font-bold rounded hover:bg-gray-900 transition-colors shadow-sm">
                  Analisar Documentos
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="activeTab === 'crm'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
           <div class="flex items-center space-x-4">
             <input v-model="userSearch" type="text" placeholder="Filtrar por nome/email..." class="text-xs font-medium border-gray-300 rounded px-3 py-1.5 w-64 focus:ring-gray-500" />
             <div v-if="roleFilter" class="flex items-center space-x-2 bg-blue-100 px-3 py-1 rounded-full border border-blue-200">
               <span class="text-[10px] font-black text-blue-800 uppercase tracking-widest">Filtro Ativo: {{ roleFilter }}</span>
               <button @click="roleFilter = ''" class="text-blue-500 hover:text-blue-800 font-bold">✕</button>
             </div>
           </div>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Usuário / Cadastro</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Contato</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status da Conta</th>
              <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Controle de Segurança</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-if="usersExibidos.length === 0"><td colspan="4" class="px-6 py-10 text-center text-gray-500 text-xs font-bold uppercase tracking-widest">Nenhum registro encontrado.</td></tr>
            <tr v-for="user in usersExibidos" :key="user.id" class="hover:bg-gray-50" :class="{'opacity-50 bg-red-50': user.status === 'banned'}">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900">{{ user.name }} <span class="text-xs text-gray-400 font-normal">({{ user.role?.name }})</span></div>
                <div class="text-xs text-gray-500">Membro desde: {{ new Date(user.created_at).toLocaleDateString('pt-BR') }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ user.email }}<br>{{ user.phone }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(user.status)">{{ user.status.toUpperCase() }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                <button v-if="user.status !== 'banned'" @click="mudarStatusUsuario(user, 'banned')" class="px-3 py-1 bg-red-600 text-white font-bold rounded shadow hover:bg-red-700 text-xs transition-colors">Banir Conta</button>
                <button v-if="user.status === 'banned'" @click="mudarStatusUsuario(user, 'active')" class="px-3 py-1 bg-gray-200 text-gray-800 font-bold rounded shadow hover:bg-gray-300 text-xs transition-colors">Restaurar Acesso</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="activeTab === 'finance'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Data Publicação</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Rota / Produto</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Operadores</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-if="fretes?.length === 0"><td colspan="4" class="px-6 py-10 text-center text-gray-500">Nenhum frete registrado ainda.</td></tr>
            <tr v-for="carga in fretes" :key="carga.id" @click="abrirPainelC3(carga)" class="hover:bg-blue-50 cursor-pointer transition-colors group">
              <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">{{ new Date(carga.created_at).toLocaleDateString('pt-BR') }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-black text-gray-900 group-hover:text-blue-600 transition-colors">{{ carga.cidade_origem }}/{{ carga.uf_origem }} ➔ {{ carga.cidade_destino }}/{{ carga.uf_destino }}</div>
                <div class="text-xs text-gray-500">{{ carga.produto }} ({{ carga.peso_kg }}kg)</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-xs">
                <div><span class="font-bold text-gray-400">EMB:</span> {{ carga.embarcador?.razao_social || 'N/A' }}</div>
                <div v-if="carga.motorista"><span class="font-bold text-gray-400">MOT:</span> {{ carga.motorista?.user?.name || 'Aguardando' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap flex justify-between items-center">
                <span class="px-2 py-1 text-xs font-black rounded-md bg-gray-100 text-gray-800 uppercase border border-gray-200">{{ carga.status?.replace('_', ' ') }}</span>
                <span class="text-blue-500 opacity-0 group-hover:opacity-100 font-black text-[10px] uppercase tracking-widest transition-opacity ml-4">Detalhes ➔</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <div v-if="showModalKyc" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="fecharKycModal"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            
            <div class="border-b border-gray-200 pb-4 mb-4 flex justify-between items-center">
              <div>
                <h3 class="text-lg font-black text-gray-900" id="modal-title">Auditoria de Documentação</h3>
                <p class="text-sm text-gray-500">Usuário: {{ usuarioSelecionado?.name }} ({{ usuarioSelecionado?.role?.name }})</p>
              </div>
              <button @click="fecharKycModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
              
              <div class="w-full lg:w-1/3 space-y-4">
                <div v-if="usuarioSelecionado?.role?.slug === 'embarcador'" class="space-y-4">
                  <div class="grid grid-cols-1 gap-2 bg-gray-50 p-4 rounded text-sm border border-gray-200">
                    <div><span class="font-bold text-gray-600 block text-xs uppercase">Razão Social</span> {{ usuarioSelecionado.embarcador?.razao_social }}</div>
                    <div><span class="font-bold text-gray-600 block text-xs uppercase">CNPJ</span> {{ usuarioSelecionado.embarcador?.cnpj }}</div>
                    <div><span class="font-bold text-gray-600 block text-xs uppercase">Endereço</span> {{ usuarioSelecionado.embarcador?.logradouro }}, {{ usuarioSelecionado.embarcador?.numero }} - {{ usuarioSelecionado.embarcador?.cidade }}/{{ usuarioSelecionado.embarcador?.uf }}</div>
                  </div>
                  
                  <div>
                    <h4 class="font-bold text-sm mb-2 uppercase tracking-wide text-gray-700">Documento Oficial</h4>
                    <button @click="selecionarDocumento(usuarioSelecionado.embarcador?.documento_kyc)" :class="btnDocClass(usuarioSelecionado.embarcador?.documento_kyc, docAtivoPath)">
                      Cartão CNPJ / Contrato Social
                    </button>
                  </div>
                </div>

                <div v-if="usuarioSelecionado?.role?.slug === 'motorista'" class="space-y-4">
                  <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded text-sm border border-gray-200">
                    <div><span class="font-bold text-gray-600 block text-xs uppercase">CPF</span> {{ usuarioSelecionado.motorista?.cpf }}</div>
                    <div><span class="font-bold text-gray-600 block text-xs uppercase">Telefone</span> {{ usuarioSelecionado.phone }}</div>
                  </div>
                  
                  <div>
                    <h4 class="font-bold text-sm mb-2 uppercase tracking-wide text-gray-700">Documentação Obrigatória</h4>
                    <div class="grid grid-cols-2 gap-2">
                      <div @click="selecionarDocumento(usuarioSelecionado.motorista?.doc_cnh)" :class="cardDocClass(usuarioSelecionado.motorista?.doc_cnh, docAtivoPath)">
                        <span class="font-bold text-xs uppercase block mb-1">CNH</span>
                        <span v-if="usuarioSelecionado.motorista?.doc_cnh" class="text-xs font-bold text-blue-600">Visualizar</span>
                        <span v-else class="text-xs font-bold text-red-500">FALTANDO</span>
                      </div>
                      <div @click="selecionarDocumento(usuarioSelecionado.motorista?.doc_selfie_cnh)" :class="cardDocClass(usuarioSelecionado.motorista?.doc_selfie_cnh, docAtivoPath)">
                        <span class="font-bold text-xs uppercase block mb-1">Selfie CNH</span>
                        <span v-if="usuarioSelecionado.motorista?.doc_selfie_cnh" class="text-xs font-bold text-blue-600">Visualizar</span>
                        <span v-else class="text-xs font-bold text-red-500">FALTANDO</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="w-full lg:w-2/3 bg-gray-100 rounded border border-gray-200 p-2 flex flex-col items-center justify-center relative min-h-[500px]">
                <iframe v-if="docAtivo" :src="docAtivo" class="w-full h-full rounded z-10 border-0 bg-white shadow-sm"></iframe>
                <div v-else class="text-gray-400 font-bold uppercase tracking-widest text-sm text-center">
                  <span class="text-3xl block mb-2">📄</span>Nenhum documento<br>selecionado
                </div>
              </div>

            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-between border-t border-gray-200">
            <button type="button" @click="processarAnalise('rejected')" :disabled="actionLoading" class="inline-flex justify-center rounded-md border border-red-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-red-700 hover:bg-red-50">
              Rejeitar Documentação
            </button>
            <div class="space-x-3">
              <button type="button" @click="fecharKycModal" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50">
                Cancelar
              </button>
              <button type="button" @click="processarAnalise('active')" :disabled="actionLoading" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-green-600 text-base font-bold text-white hover:bg-green-700">
                Aprovar Usuário
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModalC3 && cargaC3" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-gray-900/90 backdrop-blur-sm animate-fade-in">
      <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-300 max-h-[90vh]">
        
        <div class="px-6 py-4 bg-gray-900 flex justify-between items-center border-b border-gray-700 sticky top-0 z-10">
          <div class="flex items-center space-x-4">
            <span class="px-3 py-1 bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-md">ID #{{ cargaC3.id }}</span>
            <h3 class="text-lg font-black text-white uppercase tracking-wider">Painel C3 • Gêmeo Digital da Carga</h3>
          </div>
          <div class="flex items-center space-x-4">
            <span :class="getStatusBadgeClass(cargaC3.status)">{{ cargaC3.status.replace('_', ' ') }}</span>
            <button @click="fecharPainelC3" class="text-gray-400 hover:text-white font-bold text-xl transition-colors">✕</button>
          </div>
        </div>

        <div class="p-6 bg-gray-50 flex-1 overflow-y-auto">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <div class="space-y-6">
              
              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-blue-500"></div>
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Vetor de Deslocamento</h4>
                <div class="flex justify-between items-center mb-4">
                  <div class="w-2/5">
                    <p class="text-xs text-gray-500 font-bold uppercase">Origem</p>
                    <p class="text-lg font-black text-gray-900">{{ cargaC3.cidade_origem }}</p>
                    <p class="text-xs font-mono font-bold text-blue-600">{{ cargaC3.uf_origem }}</p>
                  </div>
                  <div class="w-1/5 flex justify-center">
                    <span class="text-2xl text-gray-300">➔</span>
                  </div>
                  <div class="w-2/5 text-right">
                    <p class="text-xs text-gray-500 font-bold uppercase">Destino</p>
                    <p class="text-lg font-black text-gray-900">{{ cargaC3.cidade_destino }}</p>
                    <p class="text-xs font-mono font-bold text-green-600">{{ cargaC3.uf_destino }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 mt-2">
                  <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Mercadoria</p>
                    <p class="text-sm font-black text-gray-800">{{ cargaC3.produto }} ({{ cargaC3.peso_kg }}kg)</p>
                  </div>
                  <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Veículo Exigido</p>
                    <p class="text-sm font-black text-gray-800">{{ cargaC3.tipo_veiculo }} / {{ cargaC3.tipo_carroceria }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gray-900 p-5 rounded-xl border border-gray-800 shadow-lg text-white">
                 <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Telemetria Financeira</h4>
                 <div class="grid grid-cols-2 gap-4">
                   <div>
                     <p class="text-[10px] font-bold text-gray-400 uppercase">Valor Líquido Motorista</p>
                     <p class="text-2xl font-black text-white">{{ formatMoney(cargaC3.valor_frete) }}</p>
                   </div>
                   <div class="text-right">
                     <p class="text-[10px] font-bold text-blue-400 uppercase">Receita Retida (Plataforma)</p>
                     <p class="text-2xl font-black text-blue-400">{{ formatMoney(cargaC3.taxa_plataforma) }}</p>
                   </div>
                 </div>
              </div>

            </div>

            <div class="space-y-6">
              
              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                 <div class="flex items-center space-x-3 mb-3">
                   <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                     <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" /><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>
                   </div>
                   <h4 class="text-xs font-black text-gray-900 uppercase tracking-widest">Entidade Contratante</h4>
                 </div>
                 <div v-if="cargaC3.embarcador">
                   <p class="text-base font-black text-gray-900">{{ cargaC3.embarcador.razao_social }}</p>
                   <p class="text-xs text-gray-500 font-mono">{{ cargaC3.embarcador.cnpj }}</p>
                   <p class="text-xs text-gray-400 mt-2 font-bold">{{ cargaC3.embarcador.user?.email }} • {{ cargaC3.embarcador.user?.phone }}</p>
                 </div>
                 <div v-else class="text-sm text-red-500 font-bold italic">Vínculo de Embarcador Rompido.</div>
              </div>

              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm relative">
                 <div class="flex items-center space-x-3 mb-3">
                   <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                     <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7h-3v7h.05a2.5 2.5 0 004.9 0H17a1 1 0 001-1V9l-4-2z" /></svg>
                   </div>
                   <h4 class="text-xs font-black text-gray-900 uppercase tracking-widest">Entidade Transportadora</h4>
                 </div>
                 <div v-if="cargaC3.motorista">
                   <p class="text-base font-black text-gray-900">{{ cargaC3.motorista.user?.name }}</p>
                   <p class="text-xs text-gray-500 font-mono">CPF: {{ cargaC3.motorista.cpf }} • CNH: {{ cargaC3.motorista.cnh }}</p>
                   <p class="text-xs text-gray-400 mt-2 font-bold">{{ cargaC3.motorista.user?.phone }}</p>
                 </div>
                 <div v-else class="py-4 text-center">
                   <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-[10px] font-black uppercase tracking-widest rounded animate-pulse">Aguardando Alocação no Radar</span>
                 </div>
              </div>

            </div>
          </div>
        </div>

        <div class="px-6 py-4 bg-white border-t border-gray-200 flex justify-between items-center">
          <div class="text-[10px] font-bold text-gray-400 uppercase font-mono">Publicado em: {{ new Date(cargaC3.created_at).toLocaleString('pt-BR') }}</div>
          
          <div class="flex space-x-3">
            <button 
              v-if="['entregue', 'finalizada', 'pago'].includes(cargaC3.status)" 
              @click="irParaAuditoriaDesdeC3(cargaC3.id)"
              class="px-6 py-2 bg-blue-600 text-white rounded text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-colors shadow-sm flex items-center"
            >
              Auditoria Completa (Caixa Preta) ➔
            </button>
            
            <button v-else disabled class="px-6 py-2 bg-gray-100 text-gray-400 rounded text-xs font-black uppercase tracking-widest border border-gray-200 cursor-not-allowed">
              Auditoria Indisponível (Em Trânsito)
            </button>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const activeTab = ref('overview');
const loadingGlobal = ref(true);
const actionLoading = ref(false);

const stats = ref({});
const kycQueue = ref([]);
const allUsers = ref([]);
const fretes = ref([]);

// Filtro Reactivo do CRM
const userSearch = ref('');
const roleFilter = ref('');

// Controle KYC Modal
const showModalKyc = ref(false);
const usuarioSelecionado = ref(null);
const docAtivo = ref(null);
const docAtivoPath = ref(null); // Variável para controlar o highlight do botão

// Controle Painel C3 (Cargas)
const showModalC3 = ref(false);
const cargaC3 = ref(null);

// Estilos Dinâmicos
const tabClass = (tabName) => {
  return activeTab.value === tabName 
    ? 'px-4 py-2 text-sm font-bold rounded-md bg-blue-600 text-white shadow-md transition-all'
    : 'px-4 py-2 text-sm font-medium rounded-md text-gray-300 hover:bg-gray-800 transition-all';
};

const btnDocClass = (caminhoDoc, atual) => {
  const base = "w-full text-left px-4 py-3 text-sm font-bold border rounded transition-colors block ";
  if (!caminhoDoc) return base + "bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed opacity-60";
  if (atual === caminhoDoc) return base + "bg-blue-50 text-blue-700 border-blue-500 shadow-sm";
  return base + "bg-white text-gray-700 border-gray-300 hover:bg-gray-50";
};

const cardDocClass = (caminhoDoc, atual) => {
  const base = "border p-2 rounded transition-colors flex flex-col justify-center items-center h-24 ";
  if (!caminhoDoc) return base + "bg-red-50 border-red-200 opacity-60 cursor-not-allowed";
  if (atual === caminhoDoc) return base + "bg-blue-50 border-blue-500 ring-2 ring-blue-500 shadow-sm cursor-pointer";
  return base + "bg-white border-gray-300 hover:bg-gray-50 cursor-pointer";
};

// Formatação
const formatMoney = (value) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const getStatusBadgeClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold',
    banned: 'bg-red-600 text-white px-2 py-1 rounded text-xs font-bold',
    pendente: 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold',
    em_analise: 'bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold',
    publicada: 'bg-indigo-100 text-indigo-800 px-2 py-1 rounded text-[10px] font-black border border-indigo-200',
    aguardando_motorista: 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-[10px] font-black border border-yellow-200 animate-pulse',
    em_transito: 'bg-blue-100 text-blue-800 px-2 py-1 rounded text-[10px] font-black border border-blue-200',
    entregue: 'bg-green-100 text-green-800 px-2 py-1 rounded text-[10px] font-black border border-green-200',
    em_disputa: 'bg-red-100 text-red-800 px-2 py-1 rounded text-[10px] font-black border border-red-200',
    cancelada: 'bg-gray-100 text-gray-500 px-2 py-1 rounded text-[10px] font-black border border-gray-200 line-through'
  };
  return classes[status] || 'bg-gray-100 text-gray-800 px-2 py-1 rounded text-[10px] font-black border border-gray-200';
};

// Computado de Busca + Drill-Down (Filtro Reativo CRM)
const usersExibidos = computed(() => {
  let list = allUsers.value || [];
  
  if (roleFilter.value) {
    list = list.filter(u => u.role?.slug === roleFilter.value);
  }
  
  if (userSearch.value) {
    const q = userSearch.value.toLowerCase();
    list = list.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
  }
  
  return list;
});

// Ações de Navegação
const mudarAba = (tab) => {
  activeTab.value = tab;
  if (tab !== 'crm') roleFilter.value = ''; // Limpa o filtro ao sair da aba
};

const irParaCrm = (role) => {
  activeTab.value = 'crm';
  roleFilter.value = role;
  userSearch.value = '';
};

const irParaAuditoria = () => {
  router.push({ name: 'AdminHistoricoFretes' });
};

const irParaAuditoriaDesdeC3 = (id) => {
  router.push({ name: 'AdminHistoricoFretes', query: { loadCarga: id } }); 
};

// Controle do Painel C3 (Novo)
const abrirPainelC3 = (carga) => {
  cargaC3.value = carga;
  showModalC3.value = true;
};

const fecharPainelC3 = () => {
  showModalC3.value = false;
  cargaC3.value = null;
};

// Dados API
const fetchDashboardData = async () => {
  loadingGlobal.value = true;
  try {
    const [resStats, resKyc, resUsers, resFretes] = await Promise.all([
      axios.get('/api/v1/admin/dashboard-stats'),
      axios.get('/api/v1/admin/usuarios-pendentes'),
      axios.get('/api/v1/admin/usuarios'),
      axios.get('/api/v1/admin/fretes')
    ]);
    
    stats.value = resStats.data;
    kycQueue.value = resKyc.data.data || resKyc.data;
    allUsers.value = resUsers.data.data || resUsers.data;
    fretes.value = resFretes.data.data || resFretes.data;
  } catch (error) {
    console.error('Erro ao carregar dados:', error);
  } finally {
    loadingGlobal.value = false;
  }
};

// ==========================================
// Controle KYC Modal (CORRIGIDO PARA ZERO TRUST)
// ==========================================

// Função auxiliar para gerar a URL segura usando o Token Bearer da sessão
const getKycUrl = (path) => {
  if (!path) return null;
  if (path.startsWith('http') || path.startsWith('/api/')) return path;
  return `/api/v1/admin/kyc/documento?path=${encodeURIComponent(path)}`;
};

const selecionarDocumento = (caminho) => {
  if (caminho) {
    docAtivoPath.value = caminho;
    docAtivo.value = getKycUrl(caminho);
  }
};

const abrirKycModal = (user) => {
  usuarioSelecionado.value = user;
  
  let defaultDoc = null;
  if (user.role?.slug === 'motorista') {
    defaultDoc = user.motorista?.doc_cnh || user.motorista?.doc_selfie_cnh || null;
  } else {
    defaultDoc = user.embarcador?.documento_kyc || null;
  }
  
  selecionarDocumento(defaultDoc);
  showModalKyc.value = true;
};

const fecharKycModal = () => {
  showModalKyc.value = false;
  usuarioSelecionado.value = null;
  docAtivo.value = null;
  docAtivoPath.value = null;
};

const processarAnalise = async (status) => {
  if (!confirm(`Tem certeza que deseja ${status === 'active' ? 'APROVAR' : 'REJEITAR'} este usuário?`)) return;

  actionLoading.value = true;
  try {
    const response = await axios.post(`/api/v1/admin/usuarios/${usuarioSelecionado.value.id}/analise`, { status });
    alert(response.data.message);
    fecharKycModal();
    fetchDashboardData();
  } catch (error) {
    console.error('Erro na análise:', error);
    alert(error.response?.data?.message || 'Erro de conexão.');
  } finally {
    actionLoading.value = false;
  }
};

const mudarStatusUsuario = async (user, newStatus) => {
  const acao = newStatus === 'banned' ? 'BANIR' : 'RESTAURAR';
  if(!confirm(`Atenção: Você está prestes a ${acao} o usuário ${user.name}. Confirma?`)) return;

  try {
    await axios.post(`/api/v1/admin/usuarios/${user.id}/status`, { status: newStatus });
    fetchDashboardData();
  } catch (error) { 
    alert('Erro ao alterar status de segurança.'); 
  }
};

onMounted(() => {
  fetchDashboardData();
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