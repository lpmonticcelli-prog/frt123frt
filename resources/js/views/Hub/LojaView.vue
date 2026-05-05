<template>
  <div class="space-y-6">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Loja 123fretei</h2>
        <p class="text-slate-500 mt-1">Compre EPIs e acessórios usando seu saldo de frete.</p>
      </div>
      <div class="text-right">
        <span class="text-sm text-slate-500 block">Saldo Disponível</span>
        <span class="text-2xl font-bold text-emerald-600">R$ 0,00</span>
      </div>
    </div>

    <!-- Grid de Produtos (Mockup) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div v-for="i in 3" :key="i" class="bg-white rounded-lg border border-slate-200 overflow-hidden hover:shadow-md transition">
        <div class="h-48 bg-slate-100 flex items-center justify-center text-slate-400">
          [Imagem do Produto]
        </div>
        <div class="p-4">
          <h3 class="font-bold text-slate-800">Bota de Segurança Bracol</h3>
          <p class="text-sm text-slate-500 mt-1">CA 12345 - Proteção essencial para carga e descarga.</p>
          <div class="mt-4 flex justify-between items-center">
            <span class="font-bold text-lg">R$ 145,90</span>
            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700">Comprar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Lógica de carrinho será injetada aqui no futuro
</script>