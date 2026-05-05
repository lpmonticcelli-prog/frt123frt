<template>
  <div class="space-y-6">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
      <h2 class="text-2xl font-bold text-slate-800">Meus Vouchers</h2>
      <p class="text-slate-500 mt-1">Apresente o código na rede credenciada de postos e restaurantes.</p>
    </div>

    <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-lg p-8 text-white flex flex-col items-center justify-center min-h-[300px]">
      <div class="bg-white p-4 rounded-lg mb-6">
        <!-- Espaço reservado para a biblioteca vue-qrcode-reader -->
        <div class="w-48 h-48 border-4 border-dashed border-slate-300 flex items-center justify-center text-slate-800 font-bold">
          QR CODE GERADO
        </div>
      </div>
      <h3 class="text-xl font-bold tracking-widest text-emerald-400">ABASTECIMENTO - R$ 500,00</h3>
      <p class="text-slate-400 mt-2">Validade: 24 horas</p>
    </div>
  </div>
</template>

<script setup>
// Lógica de QR Code e geração de Token JWT temporário será injetada aqui
</script>