<template>
  <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
    <div v-if="isOpen" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
        
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="$emit('close')" aria-hidden="true"></div>

        <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle w-full max-w-4xl max-h-[85vh] flex flex-col">
          
          <div class="bg-slate-900 px-6 py-4 flex justify-between items-center shrink-0">
            <h3 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
              <svg class="w-6 h-6 text-[#035D29]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              Termos de Uso e Política de Privacidade
            </h3>
            <button @click="$emit('close')" class="text-slate-400 hover:text-white transition-colors focus:outline-none">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-6 sm:p-8 scrollbar-clinical bg-slate-50 text-slate-700 space-y-6">
            
            <div class="text-center mb-8 border-b border-slate-200 pb-6">
              <h2 class="text-xl sm:text-2xl font-black text-slate-900 mb-2 uppercase tracking-tight">Termos e Condições Gerais de Licenciamento de Software e Política de Uso</h2>
              <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">123 FRETEI TECNOLOGIA LTDA | Última atualização: 03 de Setembro de 2026</p>
            </div>

            <div class="prose prose-slate max-w-none prose-headings:font-black prose-headings:text-slate-900 prose-p:text-sm prose-p:leading-relaxed prose-li:text-sm">
              <p class="font-medium bg-amber-50 p-4 border border-amber-200 rounded-xl text-amber-900">
                <strong>A ACEITAÇÃO ELETRÔNICA DESTE TERMO MEDIANTE CLICKWRAP É CONDIÇÃO ABSOLUTA E INDISPENSÁVEL PARA A UTILIZAÇÃO DA PLATAFORMA.</strong> Este documento (“Termo”) estabelece as condições gerais aplicáveis à utilização do software, plataforma web e aplicativo móvel (em conjunto, a “Plataforma”) desenvolvidos e licenciados pela <strong>123 FRETEI TECNOLOGIA LTDA.</strong>, sociedade empresária limitada, inscrita no CNPJ sob o nº <strong>62.715.353/0001-25</strong>, com sede na <strong>Rua Sergipe, nº 1541, Sala 12, Centro, Catanduva - SP, CEP 15.801-300</strong>, doravante denominada simplesmente <strong>"123FRETEI"</strong> ou <strong>"Licenciante"</strong>.
              </p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 1 – NATUREZA JURÍDICA E OBJETO DO CONTRATO</h3>
              <p><strong>1.1. Provedor de Tecnologia:</strong> A 123FRETEI atua estrita e exclusivamente como <strong>Provedora de Aplicação de Internet</strong> (SaaS - Software as a Service), outorgando aos usuários uma licença de uso temporária, não exclusiva e revogável de sua plataforma tecnológica de anúncios de cargas (matchmaking).</p>
              <p><strong>1.2. Inexistência de Atividade Operacional e Logística:</strong> A 123FRETEI <strong>NÃO</strong> é transportadora, <strong>NÃO</strong> é corretora de seguros, <strong>NÃO</strong> atua como instituição financeira e <strong>NÃO</strong> possui frota. A 123FRETEI não detém a posse de mercadorias, não transporta bens, não dita o preço da rota, tampouco emite Conhecimento de Transporte (CT-e) ou Manifesto de Documentos Fiscais (MDF-e). O frete é uma relação comercial e física negociada exclusivamente entre Embarcador e Motorista.</p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 2 – DA CURADORIA TECNOLÓGICA E O CONCEITO DE "CARGA SEGURA"</h3>
              <p><strong>2.1. Filtro de Identidade (KYC/KYB):</strong> Como diferencial tecnológico, a 123FRETEI emprega processos sistêmicos de validação de identidade e auditoria de regularidade de CNPJ na aprovação da entrada de Embarcadores. O objetivo exclusivo desta tecnologia é mitigar fraudes digitais, extinguindo a publicação dos chamados "fretes fantasmas".</p>
              <p><strong>2.2. Definição Restrita de Segurança:</strong> O Usuário concorda, atesta e declara plena compreensão de que toda nomenclatura mercadológica utilizada pela Plataforma — tais como "Cargas Seguras", "Ambiente Seguro" ou "Fretes Validados" — refere-se, estrita e exclusivamente, ao filtro tecnológico e de autenticidade documental da contraparte.</p>
              <p><strong>2.3. Isenção Absoluta de Risco Físico:</strong> A validação de identidade sistêmica <strong>NÃO</strong> se confunde com apólice de seguro de carga, segurança privada ou policiamento de rodovias. Roubos de carga, furtos, assaltos à mão armada, sequestros ou acidentes mecânicos e de trânsito durante o trajeto rodoviário consubstanciam-se como fortuito externo e fato exclusivo de terceiro, eximindo a 123FRETEI de toda e qualquer responsabilidade patrimonial, física ou moral.</p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 3 – ASSINATURA SAAS E REAJUSTE UNILATERAL DE PREÇOS</h3>
              <p><strong>3.1. Remuneração Exclusiva via Licença:</strong> A remuneração auferida pela 123FRETEI provém exclusivamente da cobrança de mensalidade (assinatura recorrente de acesso ao software sem prazo de fidelidade). A 123FRETEI não atua comissionada sobre o valor dos fretes firmados.</p>
              <p><strong>3.2. Liberdade de Reajuste:</strong> Sendo a Plataforma um modelo SaaS, a 123FRETEI reserva-se o direito absoluto de alterar os valores de sua assinatura mensal, criar novas taxas ou extinguir planos a qualquer tempo, a seu exclusivo critério comercial, independentemente de decurso de prazos anuais.</p>
              <p><strong>3.3. Anuência Tácita:</strong> Alterações no valor da assinatura serão comunicadas com antecedência mínima de 30 (trinta) dias. Caso o Usuário não concorde, assiste-lhe o direito de cancelar sua conta. A continuidade do uso após a entrada em vigor dos novos valores configurará a aceitação tácita e irrevogável das novas condições.</p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 4 – ISENÇÃO DE RESPONSABILIDADE FINANCEIRA</h3>
              <p><strong>4.1. Ausência de Fiança:</strong> A Plataforma não atua como garantidora ou avalista. Em caso de inadimplência, atraso no pagamento do frete, estornos de PIX ou "calote" por parte do Embarcador, a cobrança deverá ser realizada exclusivamente pelo Motorista contra o Embarcador.</p>
              <p><strong>4.2. Pedágio e CIOT:</strong> A obrigação legal de antecipar o Vale-Pedágio Obrigatório e gerar o CIOT recai única e incondicionalmente sobre o Embarcador dono da carga.</p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 5 – POLÍTICA DE COMPLIANCE E COMBATE À VENDA CASADA</h3>
              <p><strong>5.1. Regras de Prevenção:</strong> A 123FRETEI detém a prerrogativa de bloquear o acesso tecnológico a determinados fretes, impondo como condição sistêmica que o Motorista possua um Seguro Ativo de Acidentes Pessoais.</p>
              <p><strong>5.2. Não Configuração de Venda Casada:</strong> A Plataforma disponibiliza a aquisição instantânea de Seguro (via parceiros integrados) como mera facilidade tecnológica. Fica rigorosamente assegurado ao Usuário o direito inalienável de submeter à Plataforma uma apólice ativa de qualquer outra seguradora de sua escolha via Central de Ajuda, sujeitando-se ao prazo humano de auditoria.</p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 6 – LEI GERAL DE PROTEÇÃO DE DADOS E GPS</h3>
              <p><strong>6.1. Rastreio e Legítimo Interesse:</strong> Em estrito cumprimento à segurança do contrato de transporte firmado com o Embarcador, o Motorista outorga sua expressa aquiescência para que a 123FRETEI rastreie o sinal ininterrupto de GPS do seu aparelho no período compreendido entre a "Confirmação do Aceite" até a "Entrega Concluída".</p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 7 – TOLERÂNCIA ZERO E BANIMENTO UNILATERAL</h3>
              <p>A 123FRETEI possui o poder potestativo de banir ou suspender sumariamente a conta do Usuário nos casos de: uso de perfis falsos; assédio através das ferramentas de chat; indícios de roubo de cargas; ou inadimplência superior a 5 dias no pagamento da assinatura do software.</p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 8 – INEXISTÊNCIA DE VÍNCULO EMPREGATÍCIO</h3>
              <p>O uso da Plataforma não gera vínculo empregatício (CLT) ou relação de trabalho. O Motorista atua como gestor do próprio negócio, assumindo todos os riscos operacionais inerentes à sua atividade e ao seu caminhão, sem subordinação de carga horária.</p>

              <h3 class="text-lg mt-8 mb-3 border-b-2 border-[#035D29] inline-block pb-1">CLÁUSULA 9 – FORO COMPETENTE E INDIVISIBILIDADE</h3>
              <p>Este Termo é regido pelas Leis da República Federativa do Brasil. Fica eleito o Foro da Comarca de <strong>Catanduva - Estado de São Paulo</strong> como o único tribunal hábil a apreciar litígios oriundos do uso do software e cobrança de assinaturas da 123FRETEI. Litígios de natureza estritamente operacional e rodoviária deverão ser submetidos exclusivamente ao juízo competente e unicamente entre Embarcador e Motorista.</p>
            </div>
          </div>
          
          <div class="bg-white px-6 py-4 border-t border-slate-200 shrink-0 text-center sm:text-right">
            <button @click="$emit('close')" class="w-full sm:w-auto px-8 py-3 bg-slate-900 text-white font-black rounded-xl hover:bg-slate-800 transition-colors shadow-md focus:outline-none focus:ring-2 focus:ring-slate-900/50">
              Entendi e Quero Fechar
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