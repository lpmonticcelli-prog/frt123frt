<template>
  <div class="space-y-6">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
      <h2 class="text-2xl font-bold text-slate-800">Base de Conhecimento</h2>
      <p class="text-slate-500 mt-1">Tire suas dúvidas sobre a operação do 123fretei.</p>
    </div>
    
    <!-- Importa o componente que bate no nosso Redis -->
    <FaqAccordion />
  </div>
</template>

<script setup>
import FaqAccordion from '../../Components/FaqAccordion.vue';
</script>