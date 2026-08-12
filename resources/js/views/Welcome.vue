<template>
  <div class="landing-page scroll-smooth bg-slate-50 min-h-screen">
    
    <!-- NAVBAR (Estilo clean Localiza) -->
    <nav class="navbar shadow-sm">
      <div class="nav-brand" style="display: flex; align-items: center; gap: 8px;">
        <img src="/logo1.png" alt="Logotipo 123fretei" style="height: 32px; width: auto; object-fit: contain;" />
        <span style="font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; display: flex; align-items: baseline;">
          <span style="color: #035D29;">123</span><span style="color: var(--c-brand);">fretei</span>
        </span>
      </div>
      
      <div class="nav-links hidden lg:flex">
        <a href="#plataforma">A Plataforma</a>
        <a href="#tecnologia">Tecnologia & Risco Zero</a>
        <a href="#parceiros">Integrações</a>
      </div>
      
      <div class="nav-actions">
        <router-link :to="{ name: 'Login' }" class="btn-text">Entrar</router-link>
        <router-link :to="{ name: 'RegisterEmbarcador' }" class="btn-primary">Criar Conta</router-link>
      </div>
    </nav>

    <!-- HERO SECTION (Slider Estilo Localiza) -->
    <header class="hero-carousel-container relative overflow-hidden">
      <!-- O "Trilho" do Slider que desliza para os lados -->
      <div 
        class="hero-track flex transition-transform duration-700 ease-in-out h-full"
        :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
      >
        <!-- Slide 1: Caminhão no Pôr do Sol (Sua Imagem) -->
        <div class="slide min-w-full h-full relative flex items-center justify-center sm:justify-start px-6 sm:px-12 lg:px-24">
          <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('/pordosol.jpg');"></div>
          <div class="absolute inset-0 bg-gradient-to-r from-[#035D29] via-[#035D29]/90 to-transparent"></div>
          <div class="relative z-10 hero-content text-left">
            <span class="tagline animate-fade-in-up">Arquitetura Logística de Alta Segurança</span>
            <h1 class="title animate-fade-in-up animation-delay-200">A Primeira Malha de Fretes<br>100% <span>Auditável do Brasil.</span></h1>
            <p class="description animate-fade-in-up animation-delay-400">
              Conectamos embarcadores e transportadores autônomos sob o ecossistema mais rigoroso de segurança jurídica e biometria do mercado.
            </p>
          </div>
        </div>

        <!-- Slide 2: Pagamento Garantido / Docas (Sua Imagem) -->
        <div class="slide min-w-full h-full relative flex items-center justify-center sm:justify-start px-6 sm:px-12 lg:px-24">
          <!-- Puxando a imagem local pagamento.jpg que você acabou de adicionar -->
          <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('/pagamento.jpg');"></div>
          <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/90 to-transparent"></div>
          <div class="relative z-10 hero-content text-left">
            <span class="tagline bg-brand-500/20 text-brand-500 border-brand-500/30">Zero Inadimplência</span>
            <h1 class="title text-white">Pagamento Garantido<br>e <span>Contratos Digitais.</span></h1>
            <p class="description text-slate-300">
              A cada match na plataforma, geramos contratos com validade jurídica instantânea e garantimos o Piso da ANTT na veia.
            </p>
          </div>
        </div>

        <!-- Slide 3: Motorista Profissional (Sua Imagem) -->
        <div class="slide min-w-full h-full relative flex items-center justify-center sm:justify-start px-6 sm:px-12 lg:px-24">
          <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('/motorista.jpg');"></div>
          <div class="absolute inset-0 bg-gradient-to-r from-[#035D29] via-[#035D29]/90 to-transparent"></div>
          <div class="relative z-10 hero-content text-left">
            <span class="tagline">Ecossistema Completo</span>
            <h1 class="title">Rastreamento e Gestão<br>em <span>Tempo Real.</span></h1>
            <p class="description">
              Acompanhe sua frota terceirizada com precisão militar. Inteligência de roteirização integrada para cortar seus custos logísticos.
            </p>
          </div>
        </div>
      </div>

      <!-- Navegação do Slider (Pontos e Setas) -->
      <div class="absolute bottom-40 left-0 right-0 flex justify-center items-center gap-6 z-20">
        <button @click="prevSlide" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/30 flex items-center justify-center text-white backdrop-blur-sm transition-all focus:outline-none">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <div class="flex gap-2">
          <button v-for="n in 3" :key="n" @click="goToSlide(n-1)" :class="['w-3 h-3 rounded-full transition-all', currentSlide === n-1 ? 'bg-brand-500 w-8' : 'bg-white/50 hover:bg-white']"></button>
        </div>
        <button @click="nextSlide" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/30 flex items-center justify-center text-white backdrop-blur-sm transition-all focus:outline-none">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>
      </div>
    </header>
    
    <!-- WIDGET SOBREPOSTO (Feed ao Vivo) -->
    <section class="widget-section">
      <div class="widget-container shadow-2xl shadow-[#035D29]/10">
        
        <!-- Tabs do Widget -->
        <div class="widget-tabs">
          <button class="tab active flex items-center justify-center">
            <span class="relative flex h-3 w-3 mr-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
            Radar de Cargas (Ao Vivo)
          </button>
          <button class="tab text-slate-400 cursor-not-allowed hidden sm:flex">
            Painel do Embarcador (Requer Login)
          </button>
        </div>

        <!-- Conteúdo do Widget (Mural de Cargas Animado) -->
        <div class="widget-content relative min-h-[300px] bg-slate-50/50">
          
          <div class="flex justify-between items-center mb-4 px-2">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Últimas publicações no Brasil</h3>
            <span class="text-xs font-bold text-brand-600 bg-brand-50 px-2 py-1 rounded-md">Atualizando...</span>
          </div>

          <!-- Lista de Cargas Animada (Efeito Feed) -->
          <transition-group name="list" tag="div" class="freight-list">
            <div v-for="carga in cargasAoVivo" :key="carga.id" class="freight-card hover:border-brand-500 hover:shadow-md transition-all bg-white relative overflow-hidden group">
              <!-- Brilho lateral verde indicando "Nova Carga" -->
              <div v-if="carga.isNew" class="absolute left-0 top-0 bottom-0 w-1 bg-brand-500 shadow-[0_0_10px_rgba(255,85,0,0.8)]"></div>

              <div class="route-info">
                <div class="route-point">
                  <span class="city">{{ carga.cidade_origem }}, {{ carga.uf_origem }}</span>
                  <span class="label">Origem</span>
                </div>
                <div class="route-arrow">
                  <svg class="w-6 h-6 text-slate-300 transform transition-transform group-hover:translate-x-1 group-hover:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
                <div class="route-point text-right">
                  <span class="city">{{ carga.cidade_destino }}, {{ carga.uf_destino }}</span>
                  <span class="label">Destino</span>
                </div>
              </div>

              <div class="freight-details">
                <div class="detail-badge">
                  <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                  <span class="capitalize font-bold text-slate-700">{{ String(carga.tipo_veiculo).replace('_', ' ') }}</span>
                </div>
                <div class="detail-badge">
                  <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                  <span class="truncate max-w-[150px] font-bold text-slate-700">{{ carga.produto }}</span>
                </div>
                <div class="value-blurred group-hover/blur cursor-pointer mt-1" @click="requireLogin" title="Faça login para ver o valor exato">
                  R$ ****,**
                  <span class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/blur:opacity-100 transition-opacity whitespace-nowrap shadow-lg">Faça login para ver valores</span>
                </div>
              </div>

              <div class="freight-action">
                <button @click="requireLogin" class="btn-action w-full flex items-center justify-center">
                  Ver Detalhes e Aceitar
                  <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
              </div>
            </div>
          </transition-group>
          
          <div class="text-center mt-6 pt-4 border-t border-slate-200">
             <button @click="requireLogin" class="text-brand-600 font-black hover:underline text-sm flex items-center justify-center w-full group">
                Exibir malha completa no Painel (Mais de 3.420 cargas ativas)
                <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
             </button>
          </div>
        </div>
      </div>
    </section>

    <!-- SEÇÕES INFORMATIVAS (Estilo Cards Clean) -->
    <section id="plataforma" class="info-section">
      <div class="container mx-auto px-6 max-w-6xl">
        <div class="text-center mb-12">
          <h2 class="section-title">A evolução do transporte de cargas</h2>
          <p class="section-subtitle">Nossa meta arquitetural absorve de 1.300 parceiros iniciais até o volume de 2 milhões de usuários sem gargalos operacionais.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="feature-card">
            <div class="icon-wrapper">01</div>
            <h3>Contratos Digitais</h3>
            <p>O portal gera contratos de prestação de serviço assinados digitalmente a cada match consolidado, garantindo segurança jurídica instantânea.</p>
          </div>
          <div class="feature-card">
            <div class="icon-wrapper">02</div>
            <h3>Malha Fina </h3>
            <p>Todo cadastro exige documentação veicular e CNH, processados em background. Dados divergentes são sumariamente bloqueados.</p>
          </div>
          <div class="feature-card">
            <div class="icon-wrapper">03</div>
            <h3>Score Implacável</h3>
            <p>Operamos um sistema de ranqueamento contínuo. O histórico de pontualidade gera uma nota pública que define quem acessa os melhores fretes.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="tecnologia" class="bg-white py-20 border-t border-slate-200">
      <div class="container mx-auto px-6 max-w-6xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
          <div class="audience-box bg-slate-50 border border-slate-200">
            <h2 class="audience-title text-brand-600">Para Embarcadores</h2>
            <h3 class="font-bold text-xl mb-6 text-slate-800">Blindagem Corporativa</h3>
            <ul class="benefits-list">
              <li><strong>Filtro Biométrico Avançado:</strong> Exigimos reconhecimento facial ativo do motorista no aceite da carga.</li>
              <li><strong>Gerenciamento de Risco:</strong> API com plataformas de inteligência anulando a exposição a golpistas.</li>
              <li><strong>Governança e Conformidade:</strong> Todo o histórico é registrado, auditável e obedece ao piso da ANTT.</li>
            </ul>
            <button @click="requireLogin" class="mt-8 btn-outline w-full hover:shadow-lg hover:shadow-[#035D29]/20 transition-all">Quero Publicar Cargas</button>
          </div>
          
          <div class="audience-box bg-slate-900 border border-slate-800 text-white relative overflow-hidden">
            <!-- Efeito de brilho no card escuro -->
            <div class="absolute -right-20 -top-20 w-64 h-64 bg-emerald-500 rounded-full mix-blend-overlay filter blur-3xl opacity-20"></div>
            
            <h2 class="audience-title text-emerald-400 relative z-10">Para Motoristas</h2>
            <h3 class="font-bold text-xl mb-6 text-white relative z-10">Garantia Operacional</h3>
            <ul class="benefits-list dark relative z-10">
              <li><strong>Fim do Frete Fantasma:</strong> Chega de rodar vazio. Todo frete publicado é real e tem fundos validados.</li>
              <li><strong>Acesso à Tecnologia:</strong> Oferecemos um hub integrado para rastreadores certificados.</li>
              <li><strong>Contrato Jurídico:</strong> O "boca a boca" acabou. Cada corrida gera um contrato resguardando seus direitos.</li>
            </ul>
            <button @click="requireLogin" class="mt-8 btn-primary w-full shadow-lg relative z-10 hover:shadow-brand-500/30 transition-all">Quero Encontrar Fretes</button>
          </div>
        </div>
      </div>
    </section>

    <footer class="footer bg-white border-t border-slate-200 py-10">
      <div class="container mx-auto px-6 max-w-6xl flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="nav-brand" style="display: flex; align-items: center; gap: 8px;">
          <img src="/logo1.png" alt="Logotipo 123fretei" style="height: 24px; width: auto; object-fit: contain;" />
          <span style="font-size: 1.5rem; font-weight: 900; letter-spacing: -1px; display: flex; align-items: baseline;">
            <span style="color: #035D29;">123</span><span style="color: var(--c-brand);">fretei</span>
          </span>
        </div>
        <p class="text-sm text-slate-500 font-medium">&copy; 2026 123fretei. Engenharia de Software e Logística Integrada.</p>
      </div>
    </footer>

    <!-- MODAL DE LOGIN OBRIGATÓRIO (Gatilho de Conversão) -->
    <div class="modal-overlay" :class="{ 'active': isModalOpen }" @click.self="closeModal">
      <div class="modal-box text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 mb-6">
          <svg class="h-8 w-8 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h3 class="modal-title">Acesso Restrito</h3>
        <p class="modal-text">Para garantir a segurança da nossa malha, visualizar os valores reais e aceitar cargas, você precisa estar autenticado no sistema.</p>
        
        <div class="flex flex-col gap-3 mt-8">
          <button @click="$router.push({ name: 'Login' })" class="btn-primary w-full py-3 text-lg shadow-lg shadow-[#035D29]/20 hover:scale-[1.02] transition-transform">Fazer Login Agora</button>
          <button @click="$router.push({ name: 'RegisterMotorista' })" class="btn-text w-full py-2 border border-transparent hover:border-slate-200 rounded-xl transition-all">Sou Motorista e quero me cadastrar</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

// ================= ESTADO DO SLIDER HERO =================
const currentSlide = ref(0);
let slideInterval = null;

const nextSlide = () => {
  currentSlide.value = currentSlide.value === 2 ? 0 : currentSlide.value + 1;
};
const prevSlide = () => {
  currentSlide.value = currentSlide.value === 0 ? 2 : currentSlide.value - 1;
};
const goToSlide = (index) => {
  currentSlide.value = index;
};
const startSlideTimer = () => {
  slideInterval = setInterval(nextSlide, 5000); // Passa o slide a cada 5 segundos
};

// ================= ESTADO DO MODAL =================
const isModalOpen = ref(false);
const requireLogin = () => isModalOpen.value = true;
const closeModal = () => isModalOpen.value = false;

// ================= ESTADO DO FEED AO VIVO (SIMULADOR) =================
const cargasAoVivo = ref([]);
const loading = ref(true);
let feedInterval = null;

// Banco de Dados Cenográfico com várias rotas pelo Brasil para dar ilusão de "Vida"
const mockDatabase = [
  { cidade_origem: 'São Paulo', uf_origem: 'SP', cidade_destino: 'Curitiba', uf_destino: 'PR', tipo_veiculo: 'Carreta LS', produto: 'Eletrônicos (Paletes)' },
  { cidade_origem: 'Sorriso', uf_origem: 'MT', cidade_destino: 'Paranaguá', uf_destino: 'PR', tipo_veiculo: 'Bitrem', produto: 'Soja a Granel' },
  { cidade_origem: 'Itatiba', uf_origem: 'SP', cidade_destino: 'Belo Horizonte', uf_destino: 'MG', tipo_veiculo: 'Truck Bau', produto: 'Autopeças' },
  { cidade_origem: 'Rio Verde', uf_origem: 'MT', cidade_destino: 'Santos', uf_destino: 'PR', tipo_veiculo: 'Rodo Trem', produto: 'Milho' },
  { cidade_origem: 'Manaus', uf_origem: 'AM', cidade_destino: 'Goiânia', uf_destino: 'GO', tipo_veiculo: 'Carreta Sider', produto: 'Eletrodomésticos' },
  { cidade_origem: 'Extrema', uf_origem: 'MG', cidade_destino: 'Salvador', uf_destino: 'BA', tipo_veiculo: 'Carreta Bau', produto: 'Carga Fracionada' },
  { cidade_origem: 'Jundiaí', uf_origem: 'SP', cidade_destino: 'Porto Alegre', uf_destino: 'PR', tipo_veiculo: 'Truck Sider', produto: 'Bobinas de Papel' },
  { cidade_origem: 'Joinville', uf_origem: 'SC', cidade_destino: 'Campinas', uf_destino: 'SP', tipo_veiculo: 'Fiorino', produto: 'Carga Expressa Leve' },
];

const injectNewLoad = () => {
  // Sorteia uma carga do banco de dados falso
  const randomLoad = mockDatabase[Math.floor(Math.random() * mockDatabase.length)];
  
  // Cria um objeto novo com ID único baseado no tempo para o Vue reconhecer como item novo na Animação
  const novaCarga = { ...randomLoad, id: Date.now(), isNew: true };
  
  // Empurra pro topo da lista
  cargasAoVivo.value.unshift(novaCarga);
  
  // Remove o efeito visual de "Novo" depois de 2 segundos
  setTimeout(() => { novaCarga.isNew = false; }, 2000);

  // Se a lista ficar maior que 3 itens, remove o último suavemente
  if (cargasAoVivo.value.length > 3) {
    cargasAoVivo.value.pop();
  }
};

const fetchCargasAoVivo = async () => {
  try {
    const response = await axios.get('/api/v1/public/cargas-recentes');
    if (response.data && response.data.length > 0) {
      cargasAoVivo.value = response.data;
    } else {
      throw new Error("Usar Fallback");
    }
  } catch (error) {
    // Inicia com 3 itens
    for(let i=0; i<3; i++) {
      cargasAoVivo.value.push({ ...mockDatabase[i], id: Date.now() + i });
    }
    // Começa o simulador dinâmico: a cada 4 segundos entra uma carga nova!
    feedInterval = setInterval(injectNewLoad, 4000);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchCargasAoVivo();
  startSlideTimer();
});

onUnmounted(() => {
  if (feedInterval) clearInterval(feedInterval);
  if (slideInterval) clearInterval(slideInterval);
});
</script>

<style scoped>
.landing-page {
  --c-brand: #ff5500;
  --c-brand-hover: #e64d00;
  --c-green: #035D29;
  --c-green-dark: #023818;
  font-family: 'Inter', sans-serif;
  -webkit-font-smoothing: antialiased;
}

/* ================= ANIMAÇÕES DE ENTRADA (FADE IN UP) ================= */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
.animation-delay-200 { animation-delay: 0.2s; }
.animation-delay-400 { animation-delay: 0.4s; }

/* ================= ANIMAÇÃO DA LISTA DE FRETE (O FEED VIVO) ================= */
.list-enter-active, .list-leave-active { transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
.list-enter-from { opacity: 0; transform: translateY(-40px) scale(0.95); }
.list-leave-to { opacity: 0; transform: translateY(40px) scale(0.95); }
.list-move { transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1); }

/* ================= NAV ================= */
.navbar {
  position: sticky;
  top: 0;
  width: 100%;
  padding: 1rem 6%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(10px);
  z-index: 100;
}
.nav-links { gap: 2.5rem; }
.nav-links a { font-size: 0.9rem; font-weight: 700; color: #475569; transition: color 0.2s; }
.nav-links a:hover { color: var(--c-green); }
.nav-actions { display: flex; align-items: center; gap: 1.5rem; }
.btn-text { font-size: 0.9rem; font-weight: 800; color: #0f172a; cursor: pointer; transition: color 0.2s; }
.btn-text:hover { color: var(--c-brand); }
.btn-primary { background-color: var(--c-green); color: white; padding: 0.6rem 1.5rem; border-radius: 8px; font-size: 0.9rem; font-weight: 800; transition: all 0.2s; box-shadow: 0 4px 10px -1px rgba(3, 93, 41, 0.3); }
.btn-primary:hover { background-color: var(--c-green-dark); transform: translateY(-1px); }

/* ================= HERO CAROUSEL ================= */
.hero-carousel-container {
  height: 80vh; /* Altura generosa estilo Localiza */
  min-height: 600px;
  max-height: 800px;
  background-color: var(--c-green-dark);
}
.hero-track {
  width: 100%;
  height: 100%;
}
.slide {
  flex: 0 0 100%; /* Cada slide ocupa 100% da tela */
}
.hero-content {
  max-width: 800px;
}
.tagline {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 800;
  background: rgba(255,255,255,0.1);
  padding: 0.4rem 1rem;
  border-radius: 50px;
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(255,255,255,0.2);
  color: white;
  backdrop-filter: blur(4px);
}
.title {
  font-size: clamp(2.5rem, 4.5vw, 4.5rem);
  font-weight: 900;
  line-height: 1.1;
  letter-spacing: -1.5px;
  margin-bottom: 1.5rem;
  text-shadow: 0 4px 20px rgba(0,0,0,0.3);
  color: white;
}
.title span { color: var(--c-brand); }
.description {
  font-size: 1.15rem;
  color: #e2e8f0;
  max-width: 600px;
  line-height: 1.6;
  text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* ================= WIDGET LOCALIZA STYLE ================= */
.widget-section {
  position: relative;
  margin-top: -10rem; /* Sobrepõe o Hero */
  z-index: 10;
  padding: 0 4%;
}
.widget-container {
  max-width: 1000px;
  margin: 0 auto;
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}
.widget-tabs { display: flex; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.tab { flex: 1; padding: 1.2rem; font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.tab.active { background: white; color: var(--c-green); border-bottom: 3px solid var(--c-green); }

.widget-content { padding: 2rem; }
.freight-list { display: flex; flex-direction: column; gap: 1rem; }
.freight-card {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.route-info { display: flex; align-items: center; justify-content: space-between; }
.route-point { display: flex; flex-direction: column; }
.route-point .city { font-weight: 800; color: #0f172a; font-size: 1.1rem; }
.route-point .label { font-size: 0.7rem; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
.freight-details { display: flex; flex-direction: column; gap: 0.4rem; }
.detail-badge { display: flex; align-items: center; font-size: 0.85rem; color: #475569; font-weight: 600; }
.value-blurred {
  position: relative;
  font-size: 1.2rem;
  font-weight: 900;
  color: #cbd5e1;
  text-shadow: 0 0 12px rgba(0,0,0,0.15);
  user-select: none;
  display: inline-block;
}
.btn-action {
  background: #f8fafc;
  color: var(--c-brand);
  border: 1px solid #e2e8f0;
  padding: 0.8rem;
  border-radius: 8px;
  font-weight: 800;
  transition: all 0.2s;
}
.btn-action:hover { background: var(--c-brand); color: white; border-color: var(--c-brand); transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(255, 85, 0, 0.2);}

/* ================= INFO SECTIONS ================= */
.info-section { padding: 8rem 0 6rem 0; }
.section-title { font-size: 2.2rem; font-weight: 800; color: #0f172a; letter-spacing: -1px; margin-bottom: 1rem; }
.section-subtitle { font-size: 1.1rem; color: #64748b; max-width: 600px; margin: 0 auto; }
.feature-card { background: white; border: 1px solid #e2e8f0; padding: 2.5rem; border-radius: 16px; transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
.feature-card:hover { transform: translateY(-5px); border-color: var(--c-green); }
.icon-wrapper { font-size: 1.5rem; font-weight: 900; color: var(--c-green); background: #ecfdf5; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-bottom: 1.5rem; }
.feature-card h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 1rem; color: #0f172a; }
.feature-card p { font-size: 0.95rem; color: #64748b; line-height: 1.6; }

.audience-box { padding: 3rem; border-radius: 16px; }
.audience-title { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }
.benefits-list li { margin-bottom: 1rem; font-size: 0.95rem; color: #475569; position: relative; padding-left: 1.5rem; line-height: 1.5; font-weight: 500;}
.benefits-list li::before { content: '✓'; position: absolute; left: 0; color: var(--c-green); font-weight: 900; }
.benefits-list.dark li { color: #cbd5e1; }
.benefits-list.dark li::before { color: var(--c-brand); }

/* ================= MODAL ================= */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); z-index: 999; display: flex; justify-content: center; align-items: center; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
.modal-overlay.active { opacity: 1; pointer-events: auto; }
.modal-box { background: white; padding: 3rem 2rem; border-radius: 24px; max-width: 450px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 1px solid #e2e8f0; }
.modal-overlay.active .modal-box { transform: scale(1); }
.modal-title { font-size: 1.5rem; font-weight: 900; margin-bottom: 0.5rem; color: #0f172a; letter-spacing: -0.5px;}
.modal-text { font-size: 0.95rem; color: #64748b; line-height: 1.6; }

/* ================= RESPONSIVO ================= */
@media (max-width: 1024px) {
  .freight-card { grid-template-columns: 1fr; gap: 1rem; }
  .freight-card > div { border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; }
  .freight-card > div:last-child { border-bottom: none; padding-bottom: 0; }
  .route-info { justify-content: flex-start; gap: 1rem; }
  .route-arrow svg { transform: rotate(90deg); }
}

@media (max-width: 768px) {
  .hero-carousel-container { height: 60vh; min-height: 450px; }
  .slide { align-items: flex-end; padding-bottom: 12rem; } /* Sobe o texto no mobile pro widget não tampar */
  .title { font-size: 2.2rem; }
  .widget-section { margin-top: -6rem; }
  .widget-content { padding: 1.2rem; }
  .tab { font-size: 0.85rem; padding: 1rem 0.5rem; text-align: center; flex-direction: column; gap: 4px;}
  
  .nav-actions { gap: 0.8rem; }
  .btn-primary { padding: 0.5rem 1rem; font-size: 0.8rem; }
  
  .audience-box { padding: 2rem; }
}
</style>