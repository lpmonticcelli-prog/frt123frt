<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-[#F5F5F7] p-6 font-sans">
    
    <div class="mb-10 text-center">
      <div class="flex items-center justify-center gap-2 mb-4">
        <img src="/logo1.png" alt="Logotipo 123fretei" class="h-10 w-auto object-contain" />
        <span class="text-4xl font-extrabold tracking-tight flex items-baseline">
          <span class="text-[#035D29]">123</span><span class="text-blue-600">fretei</span>
        </span>
      </div>
      <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 mt-4">Como você deseja usar a plataforma?</h1>
      <p class="text-gray-500 mt-3 text-lg">Selecione o seu perfil para iniciarmos o cadastro.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-4xl">
      
      <!-- Cartão: MOTORISTA -->
      <router-link 
        to="/register/motorista" 
        class="group bg-white rounded-2xl shadow-sm border border-gray-200 p-8 hover:shadow-xl hover:border-blue-500 transition-all duration-300 flex flex-col h-full cursor-pointer focus:outline-none focus:ring-4 focus:ring-blue-500/20"
      >
        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">Sou Motorista</h2>
        <p class="text-gray-500 flex-grow leading-relaxed">Quero encontrar fretes, otimizar minhas rotas e aumentar a renda do meu caminhão de forma segura.</p>
        <div class="mt-8 flex items-center text-sm font-bold text-blue-600">
          Criar conta de motorista
          <svg class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
        </div>
      </router-link>

      <!-- Cartão: EMBARCADOR -->
      <router-link 
        to="/register/embarcador" 
        class="group bg-white rounded-2xl shadow-sm border border-gray-200 p-8 hover:shadow-xl hover:border-[#035D29] transition-all duration-300 flex flex-col h-full cursor-pointer focus:outline-none focus:ring-4 focus:ring-[#035D29]/20"
      >
        <div class="w-16 h-16 bg-green-50 text-[#035D29] rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#035D29] group-hover:text-white transition-colors">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-[#035D29] transition-colors">Sou Embarcador</h2>
        <p class="text-gray-500 flex-grow leading-relaxed">Sou uma indústria, transportadora ou operador logístico e preciso enviar cargas com segurança e previsibilidade.</p>
        <div class="mt-8 flex items-center text-sm font-bold text-[#035D29]">
          Criar conta corporativa
          <svg class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
        </div>
      </router-link>

    </div>

    <div class="mt-12">
      <router-link to="/login" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
        Já possui uma conta? <span class="text-blue-600 font-bold ml-1">Fazer login</span>
      </router-link>
    </div>

  </div>
</template>

<script setup>
</script>