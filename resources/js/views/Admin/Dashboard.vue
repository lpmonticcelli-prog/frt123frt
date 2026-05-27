<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    
    <!-- HEADER -->
    <div class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-surface-200 bg-surface-900 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-white tracking-tight">Centro de Comando</h2>
          <p class="text-sm text-surface-400">Monitoramento global da plataforma 123fretei.</p>
        </div>
        <div class="flex space-x-2 overflow-x-auto pb-2 md:pb-0 scrollbar-hide">
          <button @click="mudarAba('overview')" :class="tabClass('overview')" aria-label="Aba Visão Geral">Visão Geral</button>
          <button @click="mudarAba('kyc')" :class="tabClass('kyc')" aria-label="Aba Auditoria KYC">
            Auditoria KYC 
            <span v-if="kycQueue?.length" class="ml-2 bg-rose-500 text-white text-xs px-2 py-0.5 rounded-full tabular-nums">{{ kycQueue?.length }}</span>
          </button>
          <button @click="mudarAba('crm')" :class="tabClass('crm')" aria-label="Aba Base de Usuários">Base de Usuários</button>
          <button @click="mudarAba('finance')" :class="tabClass('finance')" aria-label="Aba Operações e Fretes">Operações & Fretes</button>
        </div>
      </div>
    </div>

    <!-- ERRO CRÍTICO AVISO (DEFENSIVE UI) -->
    <div v-if="erroApiCritico" class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-lg shadow-clinical-sm flex items-center justify-between animate-fade-in">
      <div class="flex items-center gap-3">
        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        <div>
          <h4 class="font-bold text-sm">Falha Crítica na API de Comando</h4>
          <p class="text-xs mt-0.5">O servidor Laravel retornou erro 500. Verifique os logs do Backend (`storage/logs/laravel.log`). Alguns painéis podem estar incompletos.</p>
        </div>
      </div>
      <button @click="fetchDashboardData" class="px-3 py-1 bg-rose-100 hover:bg-rose-200 text-rose-800 text-xs font-bold rounded border border-rose-300 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500">Tentar Novamente</button>
    </div>

    <!-- LOADER GLOBAL -->
    <div v-if="loadingGlobal" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
    </div>

    <div v-else>
      <!-- ABA 1: OVERVIEW -->
      <div v-if="activeTab === 'overview'" class="space-y-6 animate-fade-in">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-gradient-to-br from-surface-900 to-surface-800 rounded-xl p-6 shadow-clinical-lg border border-surface-700 text-white">
            <h3 class="text-surface-400 text-sm font-bold uppercase tracking-wider">Receita (Taxas Retidas)</h3>
            <p class="text-3xl font-black mt-2 text-emerald-400 tabular-nums">{{ formatMoney(stats?.receita_plataforma) }}</p>
            <p class="text-xs text-surface-400 mt-2 font-mono">Transacionado: {{ formatMoney(stats?.volume_transacionado) }}</p>
          </div>
          <div @click="irParaAuditoria" class="bg-white rounded-xl p-6 shadow-clinical-sm border border-surface-200 cursor-pointer hover:bg-brand-50 transition-colors group">
            <div class="flex justify-between items-start">
               <h3 class="text-surface-500 text-sm font-bold uppercase tracking-wider">Fretes Concluídos</h3>
               <span class="opacity-0 group-hover:opacity-100 text-brand-600 text-xs font-black uppercase tracking-widest transition-opacity">Ver ➔</span>
            </div>
            <p class="text-3xl font-black mt-2 text-surface-900 tabular-nums">{{ stats?.fretes_concluidos || 0 }}</p>
            <p class="text-xs text-brand-600 font-bold mt-2 tabular-nums">+ {{ stats?.fretes_ativos || 0 }} em andamento</p>
          </div>
          <div @click="irParaCrm('motorista')" class="bg-white rounded-xl p-6 shadow-clinical-sm border border-surface-200 cursor-pointer hover:bg-surface-50 transition-colors group">
            <div class="flex justify-between items-start">
              <h3 class="text-surface-500 text-sm font-bold uppercase tracking-wider">Motoristas Ativos</h3>
              <span class="opacity-0 group-hover:opacity-100 text-surface-400 text-xs font-black uppercase tracking-widest transition-opacity">Filtrar ➔</span>
            </div>
            <p class="text-3xl font-black mt-2 text-surface-900 tabular-nums">{{ stats?.motoristas_ativos || 0 }}</p>
            <p class="text-xs text-surface-400 mt-2">Prontos para operar</p>
          </div>
          <div @click="irParaCrm('embarcador')" class="bg-white rounded-xl p-6 shadow-clinical-sm border border-surface-200 cursor-pointer hover:bg-surface-50 transition-colors group">
            <div class="flex justify-between items-start">
               <h3 class="text-surface-500 text-sm font-bold uppercase tracking-wider">Indústrias Ativas</h3>
               <span class="opacity-0 group-hover:opacity-100 text-surface-400 text-xs font-black uppercase tracking-widest transition-opacity">Filtrar ➔</span>
            </div>
            <p class="text-3xl font-black mt-2 text-surface-900 tabular-nums">{{ stats?.embarcadores_ativos || 0 }}</p>
            <p class="text-xs text-surface-400 mt-2">Publicando fretes</p>
          </div>
        </div>
      </div>

      <!-- ABA 2: KYC -->
      <div v-if="activeTab === 'kyc'" class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden animate-fade-in">
        <div v-if="!kycQueue || kycQueue.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 bg-surface-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-surface-100">
            <svg class="w-8 h-8 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7z"></path></svg>
          </div>
          <h3 class="text-lg font-bold text-surface-900 tracking-tight">Fila Limpa</h3>
          <p class="mt-1 text-sm text-surface-500">Não há nenhum usuário aguardando análise de documentos no momento.</p>
        </div>

        <div v-else class="overflow-x-auto scrollbar-clinical">
          <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Data/Hora</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Usuário / Contato</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Tipo (Role)</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-bold text-surface-500 uppercase tracking-wider">Ação</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-surface-200">
              <tr v-for="user in kycQueue" :key="user.id" class="hover:bg-surface-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-surface-500 tabular-nums">
                  {{ formatData(user.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-bold text-surface-900">{{ user.name }}</div>
                  <div class="text-xs text-surface-500 font-mono mt-0.5">{{ user.email }} • {{ user.phone }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="['px-2 py-1 text-[10px] font-black rounded uppercase tracking-widest', user.role?.slug === 'embarcador' ? 'bg-brand-100 text-brand-800 border border-brand-200' : 'bg-fuchsia-100 text-fuchsia-800 border border-fuchsia-200']">
                    {{ user.role?.name || 'Indefinido' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200 px-2 py-1 rounded uppercase tracking-widest">
                    {{ user.status?.replace(/_/g, ' ') || 'Pendente' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <button @click="abrirKycModal(user)" class="px-4 py-2 bg-surface-800 text-white text-xs font-bold uppercase tracking-widest rounded hover:bg-surface-900 transition-colors shadow-clinical-sm focus:outline-none focus:ring-2 focus:ring-surface-500">
                    Analisar Documentos
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ABA 3: CRM -->
      <div v-if="activeTab === 'crm'" class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-surface-200 bg-surface-50 flex justify-between items-center">
           <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-3 sm:space-y-0 sm:space-x-4 w-full">
             <input v-model.trim="userSearch" type="text" placeholder="Filtrar por nome/email..." class="text-xs font-medium border-surface-300 rounded px-3 py-2 w-full sm:w-64 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-shadow" />
             <div v-if="roleFilter" class="flex items-center space-x-2 bg-brand-100 px-3 py-1.5 rounded-full border border-brand-200">
               <span class="text-[10px] font-black text-brand-800 uppercase tracking-widest">Filtro Ativo: {{ roleFilter }}</span>
               <button @click="roleFilter = ''" class="text-brand-500 hover:text-brand-800 font-bold focus:outline-none">✕</button>
             </div>
           </div>
        </div>
        
        <div class="overflow-x-auto scrollbar-clinical">
          <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Usuário / Cadastro</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Contato</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Status da Conta</th>
                <th class="px-6 py-3 text-right text-xs font-bold text-surface-500 uppercase tracking-wider">Controle de Segurança</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-200">
              <tr v-if="usersExibidos.length === 0">
                <td colspan="4" class="px-6 py-10 text-center text-surface-500 text-xs font-bold uppercase tracking-widest">Nenhum registro encontrado.</td>
              </tr>
              <tr v-for="user in usersExibidos" :key="user.id" class="hover:bg-surface-50 transition-colors" :class="{'opacity-50 bg-rose-50 hover:bg-rose-50': user.status === 'banned'}">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-bold text-surface-900">{{ user.name }} <span class="text-[10px] text-surface-400 font-black uppercase tracking-widest ml-1">({{ user.role?.name || 'S/N' }})</span></div>
                  <div class="text-[10px] text-surface-500 font-bold uppercase tracking-widest mt-0.5">Desde: {{ formatData(user.created_at) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-surface-600">
                  {{ user.email }}<br><span class="tabular-nums">{{ user.phone }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusBadgeClass(user.status)">{{ user.status?.toUpperCase() || 'UNKNOWN' }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                  <button v-if="user.status !== 'banned'" @click="mudarStatusUsuario(user, 'banned')" class="px-3 py-1.5 bg-rose-600 text-white font-bold rounded shadow-clinical-sm hover:bg-rose-700 text-xs uppercase tracking-widest transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500">Banir</button>
                  <button v-if="user.status === 'banned'" @click="mudarStatusUsuario(user, 'active')" class="px-3 py-1.5 bg-surface-200 text-surface-800 font-bold rounded shadow-clinical-sm hover:bg-surface-300 text-xs uppercase tracking-widest transition-colors focus:outline-none focus:ring-2 focus:ring-surface-500">Restaurar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ABA 4: FRETES -->
      <div v-if="activeTab === 'finance'" class="bg-white rounded-xl shadow-clinical-sm border border-surface-200 overflow-hidden animate-fade-in">
        <div class="overflow-x-auto scrollbar-clinical">
          <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Data Publicação</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Rota / Produto</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Operadores</th>
                <th class="px-6 py-3 text-right text-xs font-bold text-surface-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-200">
              <tr v-if="!fretes || fretes.length === 0">
                <td colspan="4" class="px-6 py-10 text-center text-surface-500 text-xs font-bold uppercase tracking-widest">Nenhum frete registrado.</td>
              </tr>
              <tr v-for="carga in fretes" :key="carga.id" @click="abrirPainelC3(carga)" class="hover:bg-brand-50 cursor-pointer transition-colors group">
                <td class="px-6 py-4 whitespace-nowrap text-xs text-surface-500 font-mono tabular-nums">{{ formatData(carga.created_at) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-black text-surface-900 group-hover:text-brand-600 transition-colors">{{ carga.cidade_origem }}/{{ carga.uf_origem }} ➔ {{ carga.cidade_destino }}/{{ carga.uf_destino }}</div>
                  <div class="text-xs text-surface-500 mt-0.5">{{ carga.produto }} <span class="tabular-nums">({{ carga.peso_kg }}kg)</span></div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-xs">
                  <div><span class="font-bold text-surface-400">EMB:</span> <span class="truncate max-w-[150px] inline-block align-bottom font-medium">{{ carga.embarcador?.razao_social || 'N/A' }}</span></div>
                  <div class="mt-0.5" v-if="carga.motorista"><span class="font-bold text-surface-400">MOT:</span> <span class="truncate max-w-[150px] inline-block align-bottom font-medium">{{ carga.motorista?.user?.name || 'Aguardando' }}</span></div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap flex justify-between items-center">
                  <span :class="getStatusBadgeClass(carga.status)">{{ carga.status?.replace(/_/g, ' ') || 'INDEFINIDO' }}</span>
                  <span class="text-brand-500 opacity-0 group-hover:opacity-100 font-black text-[10px] uppercase tracking-widest transition-opacity ml-4">Detalhes ➔</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- MODAL KYC -->
    <div v-if="showModalKyc" class="fixed inset-0 z-modal overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-surface-950/70 transition-opacity backdrop-blur-sm" @click="fecharKycModal" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-clinical-lg transform transition-all sm:my-8 sm:align-middle w-full max-w-6xl">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            
            <div class="border-b border-surface-200 pb-4 mb-4 flex justify-between items-center">
              <div>
                <h3 class="text-lg font-black text-surface-900 tracking-tight" id="modal-title">Auditoria de Documentação</h3>
                <p class="text-sm text-surface-500 mt-0.5">Usuário: <span class="font-bold text-surface-800">{{ usuarioSelecionado?.name }}</span> ({{ usuarioSelecionado?.role?.name || 'S/N' }})</p>
              </div>
              <button @click="fecharKycModal" class="text-surface-400 hover:text-surface-900 text-2xl font-bold focus:outline-none transition-colors">&times;</button>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
              
              <div class="w-full lg:w-1/3 space-y-4">
                <div v-if="usuarioSelecionado?.role?.slug === 'embarcador'" class="space-y-4">
                  <div class="grid grid-cols-1 gap-3 bg-surface-50 p-5 rounded-lg text-sm border border-surface-200 shadow-inner">
                    <div><span class="font-bold text-surface-500 block text-[10px] uppercase tracking-widest">Razão Social</span> <span class="font-medium text-surface-900">{{ usuarioSelecionado.embarcador?.razao_social || 'N/A' }}</span></div>
                    <div><span class="font-bold text-surface-500 block text-[10px] uppercase tracking-widest">CNPJ</span> <span class="font-mono text-surface-900">{{ usuarioSelecionado.embarcador?.cnpj || 'N/A' }}</span></div>
                    <div><span class="font-bold text-surface-500 block text-[10px] uppercase tracking-widest">Endereço</span> <span class="font-medium text-surface-900">{{ usuarioSelecionado.embarcador?.logradouro || 'N/A' }}, {{ usuarioSelecionado.embarcador?.numero || 'S/N' }} - {{ usuarioSelecionado.embarcador?.cidade || '' }}/{{ usuarioSelecionado.embarcador?.uf || '' }}</span></div>
                  </div>
                  
                  <div>
                    <h4 class="font-black text-[11px] mb-2 uppercase tracking-widest text-surface-600">Documento Oficial</h4>
                    <button @click="selecionarDocumento(usuarioSelecionado.embarcador?.documento_kyc)" :class="btnDocClass(usuarioSelecionado.embarcador?.documento_kyc, docAtivoPath)">
                      Cartão CNPJ / Contrato Social
                    </button>
                  </div>
                </div>

                <div v-if="usuarioSelecionado?.role?.slug === 'motorista'" class="space-y-4">
                  <div class="grid grid-cols-2 gap-4 bg-surface-50 p-5 rounded-lg text-sm border border-surface-200 shadow-inner">
                    <div><span class="font-bold text-surface-500 block text-[10px] uppercase tracking-widest">CPF</span> <span class="font-mono text-surface-900">{{ usuarioSelecionado.motorista?.cpf || 'N/A' }}</span></div>
                    <div><span class="font-bold text-surface-500 block text-[10px] uppercase tracking-widest">Telefone</span> <span class="font-mono text-surface-900">{{ usuarioSelecionado.phone || 'N/A' }}</span></div>
                  </div>
                  
                  <div>
                    <h4 class="font-black text-[11px] mb-2 uppercase tracking-widest text-surface-600">Documentação Obrigatória</h4>
                    <div class="grid grid-cols-2 gap-3">
                      <div @click="selecionarDocumento(usuarioSelecionado.motorista?.doc_cnh)" :class="cardDocClass(usuarioSelecionado.motorista?.doc_cnh, docAtivoPath)">
                        <span class="font-black text-[10px] uppercase block mb-1 tracking-widest">CNH</span>
                        <span v-if="usuarioSelecionado.motorista?.doc_cnh" class="text-xs font-bold text-brand-600">Visualizar</span>
                        <span v-else class="text-xs font-bold text-rose-500">FALTANDO</span>
                      </div>
                      <div @click="selecionarDocumento(usuarioSelecionado.motorista?.doc_selfie_cnh)" :class="cardDocClass(usuarioSelecionado.motorista?.doc_selfie_cnh, docAtivoPath)">
                        <span class="font-black text-[10px] uppercase block mb-1 tracking-widest">Selfie CNH</span>
                        <span v-if="usuarioSelecionado.motorista?.doc_selfie_cnh" class="text-xs font-bold text-brand-600">Visualizar</span>
                        <span v-else class="text-xs font-bold text-rose-500">FALTANDO</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="w-full lg:w-2/3 bg-surface-100 rounded-lg border border-surface-200 p-2 flex flex-col items-center justify-center relative min-h-[500px] overflow-hidden">
                <iframe v-if="docAtivo" :src="docAtivo" class="w-full h-full rounded z-10 border-0 bg-white shadow-clinical-sm"></iframe>
                <div v-else class="text-surface-400 font-black uppercase tracking-widest text-xs text-center">
                  <svg class="w-12 h-12 mx-auto mb-3 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  Nenhum documento<br>selecionado
                </div>
              </div>

            </div>
          </div>
          
          <div class="bg-surface-50 px-4 py-4 sm:px-6 flex flex-col sm:flex-row sm:justify-between border-t border-surface-200 gap-3">
            <button type="button" @click="processarAnalise('rejected')" :disabled="actionLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-rose-300 shadow-clinical-sm px-6 py-2.5 bg-white text-sm font-bold text-rose-700 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 transition-colors disabled:opacity-50 uppercase tracking-widest">
              Rejeitar Documentação
            </button>
            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
              <button type="button" @click="fecharKycModal" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-surface-300 shadow-clinical-sm px-6 py-2.5 bg-white text-sm font-bold text-surface-700 hover:bg-surface-50 focus:outline-none focus:ring-2 focus:ring-surface-500 transition-colors uppercase tracking-widest">
                Cancelar
              </button>
              <button type="button" @click="processarAnalise('active')" :disabled="actionLoading" class="w-full sm:w-auto inline-flex justify-center items-center rounded-md border border-transparent shadow-clinical-sm px-8 py-2.5 bg-emerald-600 text-sm font-bold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors disabled:opacity-50 uppercase tracking-widest">
                <svg v-if="actionLoading" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Aprovar Usuário
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL C3 -->
    <div v-if="showModalC3 && cargaC3" class="fixed inset-0 z-modal overflow-y-auto flex items-center justify-center p-4 bg-surface-950/90 backdrop-blur-sm animate-fade-in">
      <div class="bg-white w-full max-w-5xl rounded-xl shadow-clinical-lg overflow-hidden flex flex-col border border-surface-300 max-h-[90vh]">
        
        <div class="px-4 sm:px-6 py-4 bg-surface-900 flex flex-wrap justify-between items-center border-b border-surface-700 sticky top-0 z-10 gap-3">
          <div class="flex items-center space-x-3 sm:space-x-4">
            <span class="px-2 sm:px-3 py-1 bg-brand-600 text-white text-[10px] font-black uppercase tracking-widest rounded-md shrink-0">ID #{{ cargaC3.id }}</span>
            <h3 class="text-sm sm:text-lg font-black text-white uppercase tracking-wider truncate">Painel C3 • Gêmeo Digital</h3>
          </div>
          <div class="flex items-center space-x-4 ml-auto">
            <span :class="getStatusBadgeClass(cargaC3.status)" class="hidden sm:inline-block">{{ cargaC3.status?.replace(/_/g, ' ') || 'INDEFINIDO' }}</span>
            <button @click="fecharPainelC3" class="text-surface-400 hover:text-white font-bold text-2xl transition-colors focus:outline-none leading-none">&times;</button>
          </div>
        </div>

        <div class="p-4 sm:p-6 bg-surface-50 flex-1 overflow-y-auto scrollbar-clinical">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <div class="space-y-6">
              <div class="bg-white p-5 rounded-xl border border-surface-200 shadow-clinical-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 w-1.5 h-full bg-brand-500"></div>
                <h4 class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-4">Vetor de Deslocamento</h4>
                <div class="flex justify-between items-center mb-4">
                  <div class="w-2/5 min-w-0">
                    <p class="text-[10px] text-surface-500 font-bold uppercase tracking-widest">Origem</p>
                    <p class="text-sm sm:text-base font-black text-surface-900 truncate" :title="cargaC3.cidade_origem">{{ cargaC3.cidade_origem || 'N/A' }}</p>
                    <p class="text-xs font-mono font-bold text-brand-600">{{ cargaC3.uf_origem || 'XX' }}</p>
                  </div>
                  <div class="w-1/5 flex justify-center shrink-0">
                    <span class="text-xl sm:text-2xl text-surface-300" aria-hidden="true">➔</span>
                  </div>
                  <div class="w-2/5 text-right min-w-0">
                    <p class="text-[10px] text-surface-500 font-bold uppercase tracking-widest">Destino</p>
                    <p class="text-sm sm:text-base font-black text-surface-900 truncate" :title="cargaC3.cidade_destino">{{ cargaC3.cidade_destino || 'N/A' }}</p>
                    <p class="text-xs font-mono font-bold text-emerald-600">{{ cargaC3.uf_destino || 'XX' }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-4 border-t border-surface-100 pt-4 mt-2">
                  <div class="min-w-0">
                    <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Mercadoria</p>
                    <p class="text-xs sm:text-sm font-black text-surface-800 truncate" :title="cargaC3.produto">{{ cargaC3.produto || 'N/A' }} <span class="font-normal text-surface-500 tabular-nums">({{ cargaC3.peso_kg || 0 }}kg)</span></p>
                  </div>
                  <div class="text-right min-w-0">
                    <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Veículo Exigido</p>
                    <p class="text-xs sm:text-sm font-black text-surface-800 capitalize truncate">{{ cargaC3.tipo_veiculo?.replace(/_/g, ' ') || 'N/A' }} / {{ cargaC3.tipo_carroceria?.replace(/_/g, ' ') || 'N/A' }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-surface-900 p-5 rounded-xl border border-surface-800 shadow-clinical-lg text-white">
                 <h4 class="text-[10px] font-black text-surface-500 uppercase tracking-widest mb-4">Telemetria Financeira</h4>
                 <div class="grid grid-cols-2 gap-4">
                   <div>
                     <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Valor Líquido</p>
                     <p class="text-xl sm:text-2xl font-black text-white tabular-nums">{{ formatMoney(cargaC3.valor_frete) }}</p>
                   </div>
                   <div class="text-right">
                     <p class="text-[10px] font-bold text-brand-400 uppercase tracking-widest">Taxa Plataforma</p>
                     <p class="text-xl sm:text-2xl font-black text-brand-400 tabular-nums">{{ formatMoney(cargaC3.taxa_plataforma) }}</p>
                   </div>
                 </div>
              </div>
            </div>

            <div class="space-y-6">
              <div class="bg-white p-5 rounded-xl border border-surface-200 shadow-clinical-sm min-h-[140px]">
                 <div class="flex items-center space-x-3 mb-3">
                   <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center shrink-0">
                     <svg class="w-4 h-4 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" /><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>
                   </div>
                   <h4 class="text-[11px] font-black text-surface-900 uppercase tracking-widest">Entidade Contratante</h4>
                 </div>
                 <div v-if="cargaC3.embarcador">
                   <p class="text-sm sm:text-base font-black text-surface-900 truncate" :title="cargaC3.embarcador.razao_social">{{ cargaC3.embarcador.razao_social }}</p>
                   <p class="text-xs text-surface-500 font-mono mt-0.5">CNPJ: {{ cargaC3.embarcador.cnpj || 'N/A' }}</p>
                   <p class="text-xs text-surface-400 mt-2 font-bold">{{ cargaC3.embarcador.user?.email || 'Sem email' }} • {{ cargaC3.embarcador.user?.phone || 'Sem telefone' }}</p>
                 </div>
                 <div v-else class="text-xs sm:text-sm text-rose-500 font-bold italic mt-4">Vínculo de Embarcador não encontrado.</div>
              </div>

              <div class="bg-white p-5 rounded-xl border border-surface-200 shadow-clinical-sm relative min-h-[140px]">
                 <div class="flex items-center space-x-3 mb-3">
                   <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                     <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7h-3v7h.05a2.5 2.5 0 004.9 0H17a1 1 0 001-1V9l-4-2z" /></svg>
                   </div>
                   <h4 class="text-[11px] font-black text-surface-900 uppercase tracking-widest">Entidade Transportadora</h4>
                 </div>
                 <div v-if="cargaC3.motorista">
                   <p class="text-sm sm:text-base font-black text-surface-900 truncate" :title="cargaC3.motorista.user?.name">{{ cargaC3.motorista.user?.name || 'Sem Nome' }}</p>
                   <p class="text-xs text-surface-500 font-mono mt-0.5">CPF: {{ cargaC3.motorista.cpf || 'N/A' }} • CNH: {{ cargaC3.motorista.cnh || 'N/A' }}</p>
                   <p class="text-xs text-surface-400 mt-2 font-bold">{{ cargaC3.motorista.user?.phone || 'Sem telefone' }}</p>
                 </div>
                 <div v-else class="py-4 text-center">
                   <span class="px-3 py-1 bg-amber-100 text-amber-800 text-[10px] font-black uppercase tracking-widest rounded animate-pulse">Aguardando Motorista</span>
                 </div>
              </div>
            </div>
          </div>
        </div>

        <div class="px-4 sm:px-6 py-4 bg-white border-t border-surface-200 flex flex-col sm:flex-row justify-between items-center gap-3">
          <div class="text-[10px] font-bold text-surface-400 uppercase font-mono text-center sm:text-left">Publicado: {{ formatData(cargaC3.created_at, true) }}</div>
          
          <div class="flex w-full sm:w-auto">
            <button 
              v-if="['entregue', 'finalizada', 'pago'].includes(cargaC3.status)" 
              @click="irParaAuditoriaDesdeC3(cargaC3.id)"
              class="w-full sm:w-auto px-6 py-2.5 bg-brand-600 text-white rounded-md text-[11px] font-black uppercase tracking-widest hover:bg-brand-700 transition-colors shadow-clinical-sm flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
            >
              Auditoria Completa (Caixa Preta) ➔
            </button>
            <button v-else disabled class="w-full sm:w-auto px-6 py-2.5 bg-surface-100 text-surface-400 rounded-md text-[11px] font-black uppercase tracking-widest border border-surface-200 cursor-not-allowed">
              Auditoria Restrita (Operação Ativa)
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
const erroApiCritico = ref(false);

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
const docAtivoPath = ref(null); 

// Controle Painel C3 (Cargas)
const showModalC3 = ref(false);
const cargaC3 = ref(null);

// Estilos Dinâmicos e Paleta Corporativa
const tabClass = (tabName) => {
  return activeTab.value === tabName 
    ? 'px-4 py-2 text-sm font-bold rounded-md bg-brand-600 text-white shadow-clinical-sm transition-all whitespace-nowrap'
    : 'px-4 py-2 text-sm font-medium rounded-md text-surface-400 hover:bg-surface-800 hover:text-white transition-all whitespace-nowrap';
};

const btnDocClass = (caminhoDoc, atual) => {
  const base = "w-full text-left px-4 py-3 text-sm font-bold border rounded transition-colors block focus:outline-none focus:ring-2 focus:ring-brand-500 ";
  if (!caminhoDoc) return base + "bg-surface-50 text-surface-400 border-surface-200 cursor-not-allowed opacity-60";
  if (atual === caminhoDoc) return base + "bg-brand-50 text-brand-700 border-brand-500 shadow-clinical-sm";
  return base + "bg-white text-surface-700 border-surface-300 hover:bg-surface-50";
};

const cardDocClass = (caminhoDoc, atual) => {
  const base = "border p-2 rounded transition-colors flex flex-col justify-center items-center h-24 focus:outline-none focus:ring-2 focus:ring-brand-500 ";
  if (!caminhoDoc) return base + "bg-rose-50 border-rose-200 opacity-60 cursor-not-allowed";
  if (atual === caminhoDoc) return base + "bg-brand-50 border-brand-500 ring-2 ring-brand-500 shadow-clinical-sm cursor-pointer";
  return base + "bg-white border-surface-300 hover:bg-surface-50 cursor-pointer";
};

// Formatação Defensiva: Extração do Intl para Singleton
const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const formatMoney = (value) => {
  const num = parseFloat(value);
  return isNaN(num) ? 'R$ 0,00' : moneyFormatter.format(num);
};

// Formatação Defensiva de Datas
const formatData = (dataStr, showTime = false) => {
  if (!dataStr) return '--';
  try {
    const date = new Date(dataStr);
    if (isNaN(date.getTime())) return dataStr;
    return showTime ? date.toLocaleString('pt-BR') : date.toLocaleDateString('pt-BR');
  } catch (e) {
    return '--';
  }
};

const getStatusBadgeClass = (status) => {
  if (!status || typeof status !== 'string') return 'bg-surface-100 text-surface-800 px-2 py-1 rounded text-[10px] font-black border border-surface-200 uppercase tracking-widest';
  
  const classes = {
    active: 'bg-emerald-100 text-emerald-800 px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest',
    banned: 'bg-rose-600 text-white px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest',
    pendente: 'bg-amber-100 text-amber-800 px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest',
    em_analise: 'bg-brand-100 text-brand-800 px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest',
    publicada: 'bg-indigo-100 text-indigo-800 px-2 py-1 rounded text-[10px] font-black border border-indigo-200 uppercase tracking-widest',
    aguardando_motorista: 'bg-amber-100 text-amber-800 px-2 py-1 rounded text-[10px] font-black border border-amber-200 animate-pulse uppercase tracking-widest',
    em_transito: 'bg-brand-100 text-brand-800 px-2 py-1 rounded text-[10px] font-black border border-brand-200 uppercase tracking-widest',
    entregue: 'bg-emerald-100 text-emerald-800 px-2 py-1 rounded text-[10px] font-black border border-emerald-200 uppercase tracking-widest',
    em_disputa: 'bg-rose-100 text-rose-800 px-2 py-1 rounded text-[10px] font-black border border-rose-200 uppercase tracking-widest',
    cancelada: 'bg-surface-100 text-surface-500 px-2 py-1 rounded text-[10px] font-black border border-surface-200 line-through uppercase tracking-widest'
  };
  return classes[status] || 'bg-surface-100 text-surface-800 px-2 py-1 rounded text-[10px] font-black border border-surface-200 uppercase tracking-widest';
};

// Computado de Busca + Drill-Down (Filtro Reativo CRM)
const usersExibidos = computed(() => {
  let list = Array.isArray(allUsers.value) ? allUsers.value : [];
  
  if (roleFilter.value) {
    list = list.filter(u => u.role?.slug === roleFilter.value);
  }
  
  if (userSearch.value) {
    const q = userSearch.value.toLowerCase();
    list = list.filter(u => 
      (u.name && u.name.toLowerCase().includes(q)) || 
      (u.email && u.email.toLowerCase().includes(q))
    );
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
  if(!id) return;
  router.push({ name: 'AdminHistoricoFretes', query: { loadCarga: id } }); 
};

// Controle do Painel C3
const abrirPainelC3 = (carga) => {
  if(!carga) return;
  cargaC3.value = carga;
  showModalC3.value = true;
};

const fecharPainelC3 = () => {
  showModalC3.value = false;
  cargaC3.value = null;
};

// ==========================================
// ARQUITETURA DEFENSIVA: FETCH ISOLADO
// ==========================================
// Substituto do Promise.all() que quebrava toda a tela se a rota /fretes falhasse.
const fetchDashboardData = async () => {
  loadingGlobal.value = true;
  erroApiCritico.value = false;
  
  // Limpa estados antes do fetch
  stats.value = {};
  kycQueue.value = [];
  allUsers.value = [];
  fretes.value = [];

  let falhas = 0;

  try {
    const resStats = await axios.get('/api/v1/admin/dashboard-stats');
    stats.value = resStats.data || {};
  } catch (error) { falhas++; console.error('[API] Falha em Stats:', error); }

  try {
    const resKyc = await axios.get('/api/v1/admin/usuarios-pendentes');
    kycQueue.value = resKyc.data?.data || resKyc.data || [];
  } catch (error) { falhas++; console.error('[API] Falha em KYC:', error); }

  try {
    const resUsers = await axios.get('/api/v1/admin/usuarios');
    allUsers.value = resUsers.data?.data || resUsers.data || [];
  } catch (error) { falhas++; console.error('[API] Falha em CRM:', error); }

  try {
    const resFretes = await axios.get('/api/v1/admin/fretes');
    fretes.value = resFretes.data?.data || resFretes.data || [];
  } catch (error) { falhas++; console.error('[API] Falha em Fretes:', error); }

  // Se tudo falhou (provavelmente o servidor caiu), exibe o banner vermelho.
  if (falhas >= 4) {
    erroApiCritico.value = true;
  }

  loadingGlobal.value = false;
};

// ==========================================
// Controle KYC Modal
// ==========================================
const getKycUrl = (path) => {
  if (!path || typeof path !== 'string') return null;
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
  if(!user) return;
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
  if (!usuarioSelecionado.value?.id) return;
  if (!confirm(`Tem certeza que deseja ${status === 'active' ? 'APROVAR' : 'REJEITAR'} este usuário?`)) return;

  actionLoading.value = true;
  try {
    const response = await axios.post(`/api/v1/admin/usuarios/${usuarioSelecionado.value.id}/analise`, { status });
    alert(response.data.message || 'Status alterado com sucesso.');
    fecharKycModal();
    // Atualiza apenas as filas afetadas para poupar rede
    fetchDashboardData();
  } catch (error) {
    console.error('[API] Erro na análise KYC:', error);
    alert(error.response?.data?.message || 'Falha de comunicação com o Backend.');
  } finally {
    actionLoading.value = false;
  }
};

const mudarStatusUsuario = async (user, newStatus) => {
  if(!user || !user.id) return;
  const acao = newStatus === 'banned' ? 'BANIR' : 'RESTAURAR';
  if(!confirm(`Atenção: Você está prestes a ${acao} o usuário ${user.name}. Confirma?`)) return;

  try {
    await axios.post(`/api/v1/admin/usuarios/${user.id}/status`, { status: newStatus });
    fetchDashboardData();
  } catch (error) { 
    console.error('[API] Falha ao alterar status da conta:', error);
    alert('Erro ao alterar status de segurança. O servidor pode estar inacessível.'); 
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
/* Oculta scrollbar na navegação de abas no mobile */
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>