<template>
  <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
    <div v-if="isOpen" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
        
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle w-full max-w-4xl max-h-[85vh] flex flex-col">
          
          <!-- Cabeçalho -->
          <div class="bg-slate-900 px-6 py-4 flex justify-between items-center shrink-0">
            <h3 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
              <svg class="w-6 h-6 text-[#035D29]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              Condições Gerais do Seguro
            </h3>
            <button @click="$emit('close')" class="text-slate-400 hover:text-white transition-colors focus:outline-none">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>

          <!-- Corpo Rolável -->
          <div class="flex-1 overflow-y-auto p-6 scrollbar-clinical bg-slate-50">
            
            <!-- Alertas Críticos -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-8">
              <h4 class="font-black text-amber-900 mb-3 uppercase tracking-widest text-xs">Regras e Restrições Importantes</h4>
              <ul class="space-y-2 text-sm text-amber-800 font-medium">
                <li class="flex items-start gap-2">
                  <span class="mt-0.5">•</span>
                  <span><strong>Idade Limite:</strong> O seguro é válido apenas para motoristas entre 18 e 70 anos.</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="mt-0.5">•</span>
                  <span><strong>Carência (15 dias):</strong> As coberturas de Diárias por Incapacidade Temporária (DIT) e Despesas Médico-Hospitalares (DMHO) possuem carência de 15 dias a partir da emissão.</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="mt-0.5">•</span>
                  <span><strong>Franquia (5 dias):</strong> A cobertura de Diárias por Incapacidade (DIT) possui franquia de 5 dias. A cobertura de DMHO é isenta de franquia.</span>
                </li>
                <li class="flex items-start gap-2">
                  <span class="mt-0.5">•</span>
                  <span><strong>Inadimplência:</strong> O atraso no pagamento suspende as garantias do seguro por 15 dias.</span>
                </li>
              </ul>
            </div>

            <!-- Tabela Comparativa de Coberturas -->
            <div class="mb-8">
              <h4 class="font-black text-slate-800 mb-4 text-lg">Comparativo de Capitais Segurados</h4>
              <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm bg-white">
                  <thead class="bg-slate-100">
                    <tr>
                      <th class="px-4 py-3 text-left font-black text-slate-600 uppercase">Cobertura Principal</th>
                      <th class="px-4 py-3 text-center font-black text-slate-600 uppercase">Básico</th>
                      <th class="px-4 py-3 text-center font-black text-[#035D29] uppercase bg-emerald-50">Intermediário</th>
                      <th class="px-4 py-3 text-center font-black text-slate-600 uppercase">Superior</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <tr>
                      <td class="px-4 py-3">Morte Acidental</td>
                      <td class="px-4 py-3 text-center">R$ 30.000</td>
                      <td class="px-4 py-3 text-center bg-emerald-50/30">R$ 50.000</td>
                      <td class="px-4 py-3 text-center">R$ 80.000</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-3">Invalidez por Acidente</td>
                      <td class="px-4 py-3 text-center">R$ 30.000</td>
                      <td class="px-4 py-3 text-center bg-emerald-50/30">R$ 50.000</td>
                      <td class="px-4 py-3 text-center">R$ 80.000</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-3">Auxílio Funeral</td>
                      <td class="px-4 py-3 text-center">R$ 5.000</td>
                      <td class="px-4 py-3 text-center bg-emerald-50/30">R$ 7.000</td>
                      <td class="px-4 py-3 text-center">R$ 10.000</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-3 text-[#035D29] font-bold">Diárias de Incapacidade (DIT)</td>
                      <td class="px-4 py-3 text-center font-bold">R$ 3.000</td>
                      <td class="px-4 py-3 text-center bg-emerald-50/30 font-bold">R$ 3.000</td>
                      <td class="px-4 py-3 text-center font-bold">R$ 3.000</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-3 text-[#035D29] font-bold">Despesas Médicas (DMHO)</td>
                      <td class="px-4 py-3 text-center font-bold">R$ 5.000</td>
                      <td class="px-4 py-3 text-center bg-emerald-50/30 font-bold">R$ 5.000</td>
                      <td class="px-4 py-3 text-center font-bold">R$ 5.000</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-3">Telemedicina</td>
                      <td class="px-4 py-3 text-center">Individual</td>
                      <td class="px-4 py-3 text-center bg-emerald-50/30">Individual</td>
                      <td class="px-4 py-3 text-center font-bold">Familiar</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Assistência Residencial -->
            <div>
              <h4 class="font-black text-slate-800 mb-4 text-lg">Limites da Assistência Residencial 24h</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                  <h5 class="font-bold text-slate-900 border-b border-slate-100 pb-2 mb-3">Plano Básico</h5>
                  <ul class="text-xs text-slate-600 space-y-2">
                    <li>• Chaveiro e Eletricista (Até R$ 200)</li>
                    <li>• Desentupimento (Até R$ 250)</li>
                    <li>• Vidraceiro (Até R$ 450)</li>
                  </ul>
                </div>

                <div class="bg-emerald-50 p-4 rounded-xl border border-[#035D29]/20">
                  <h5 class="font-bold text-[#035D29] border-b border-[#035D29]/10 pb-2 mb-3">Plano Intermediário</h5>
                  <ul class="text-xs text-slate-700 space-y-2">
                    <li class="font-medium">Tudo do Básico, mais:</li>
                    <li>• Encanador (Até R$ 200)</li>
                    <li>• Limpeza Caixa D'água (Até R$ 500)</li>
                    <li>• Limpeza de Residência (Até R$ 500)</li>
                  </ul>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200">
                  <h5 class="font-bold text-slate-900 border-b border-slate-100 pb-2 mb-3">Plano Superior</h5>
                  <ul class="text-xs text-slate-600 space-y-2">
                    <li class="font-medium">Tudo do Intermediário, mais:</li>
                    <li>• Caça Vazamentos (Até R$ 1.000)</li>
                    <li>• Reparo Eletrodomésticos (Até R$ 550)</li>
                    <li>• Manutenção Ar Condicionado (Até R$ 350)</li>
                    <li>• Check Up Domiciliar (Até R$ 350)</li>
                  </ul>
                </div>

              </div>
            </div>

          </div>
          
          <div class="bg-white px-6 py-4 border-t border-slate-200 shrink-0 text-center sm:text-right">
            <button @click="$emit('close')" class="w-full sm:w-auto px-6 py-3 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition-colors">
              Entendi e Quero Voltar
            </button>
          </div>

        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true
  }
});
defineEmits(['close']);
</script>

<style scoped>
.scrollbar-clinical::-webkit-scrollbar { width: 6px; height: 6px; }
.scrollbar-clinical::-webkit-scrollbar-track { background: transparent; }
.scrollbar-clinical::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.scrollbar-clinical:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
</style>