<template>
  <div class="space-y-6">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
      <h2 class="text-2xl font-bold text-slate-800">Parceiros Estratégicos</h2>
      <p class="text-slate-500 mt-1">Conecte seu ERP ou acesse descontos da nossa rede.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Card Integração API -->
      <div class="bg-white p-6 rounded-lg border border-slate-200 border-l-4 border-l-blue-600">
        <h3 class="font-bold text-lg mb-2">Integração API Rest</h3>
        <p class="text-slate-600 text-sm mb-4">Gere sua chave Bearer Token para injetar cargas automaticamente do seu sistema (SAP, TOTVS) para o 123fretei.</p>
        <button class="text-blue-600 font-semibold text-sm hover:underline">Gerar Token de Produção</button>
      </div>
      
      <!-- Card Descontos -->
      <div class="bg-white p-6 rounded-lg border border-slate-200 border-l-4 border-l-emerald-500">
        <h3 class="font-bold text-lg mb-2">Rede Ipiranga (Frota)</h3>
        <p class="text-slate-600 text-sm mb-4">Motoristas da plataforma possuem 5% de cashback na rede Ipiranga.</p>
        <button class="text-emerald-600 font-semibold text-sm hover:underline">Vincular CPF</button>
      </div>
    </div>
  </div>
</template>

<script setup>
// Lógica de geração de tokens via Laravel Sanctum (Tokens de API externa)
</script>