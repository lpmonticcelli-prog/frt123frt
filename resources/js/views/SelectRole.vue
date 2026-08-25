<template>
  <div style="padding: 100px; text-align: center; background-color: #f5f5f5; height: 100vh;">
    <h1>A TELA ESTÁ FUNCIONANDO!</h1>
    <p>Se você está vendo isso, o roteador está certo.</p>
    
    <div style="margin-top: 50px; display: flex; justify-content: center; gap: 20px;">
      <router-link to="/register/motorista" style="padding: 20px; background: blue; color: white; text-decoration: none; border-radius: 8px;">Sou Motorista</router-link>
      <router-link to="/register/embarcador" style="padding: 20px; background: green; color: white; text-decoration: none; border-radius: 8px;">Sou Embarcador</router-link>
    </div>
  </div>
</template>

<script setup>
</script>