<template>
  <!-- Roteador Principal -->
  <router-view />
  
  <!-- Modal Global de Bloqueio Jurídico (Clickwrap Retroativo) -->
  <TermosRetroativoModal 
    v-if="precisaAceitarTermos" 
    @resolvido="fecharERecarregar" 
  />
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import TermosRetroativoModal from './Components/TermosRetroativoModal.vue'; // Ajuste o caminho se sua pasta for diferente

const precisaAceitarTermos = ref(false);

// Escuta o "grito" do Axios (interceptado no bootstrap.js)
const handleTermosPendentes = () => {
    precisaAceitarTermos.value = true;
};

// Após a API retornar sucesso no aceite, recarrega a página para buscar os dados travados
const fecharERecarregar = () => {
    precisaAceitarTermos.value = false;
    window.location.reload(); 
};

// Registra os ouvintes globais na montagem da raiz
onMounted(() => {
    window.addEventListener('termo-pendente-detectado', handleTermosPendentes);
});

onUnmounted(() => {
    window.removeEventListener('termo-pendente-detectado', handleTermosPendentes);
});
</script>