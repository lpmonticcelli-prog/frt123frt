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

    <!-- HERO SECTION (Fundo sólido Verde da Marca) -->
    <header class="hero-section">
      <div class="hero-content">
        <span class="tagline">Arquitetura Logística Zero Trust</span>
        <h1 class="title">A Primeira Malha de Fretes<br>100% <span>Auditável do Brasil.</span></h1>
        <p class="description">
          Conectamos embarcadores e transportadores autônomos sob o ecossistema mais rigoroso de segurança jurídica e biometria do mercado.
        </p>
      </div>
    </header>

    <!-- WIDGET SOBREPOSTO (O "Coração" estilo Localiza) -->
    <section class="widget-section">
      <div class="widget-container">
        
        <!-- Tabs do Widget -->
        <div class="widget-tabs">
          <button class="tab active">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            Cargas Disponíveis Agora
          </button>
          <button class="tab text-slate-400 cursor-not-allowed">
            Painel do Embarcador (Requer Login)
          </button>
        </div>

        <!-- Conteúdo do Widget (Mural de Cargas) -->
        <div class="widget-content">
          <div class="freight-list">
            
            <!-- Carga Item -->
            <div v-for="carga in cargasAoVivo" :key="carga.id" class="freight-card hover:border-brand-500 transition-colors">
              <div class="route-info">
                <div class="route-point">
                  <span class="city">{{ carga.origem }}</span>
                  <span class="label">Origem</span>
                </div>
                <div class="route-arrow">
                  <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
                <div class="route-point text-right">
                  <span class="city">{{ carga.destino }}</span>
                  <span class="label">Destino</span>
                </div>
              </div>

              <div class="freight-details">
                <div class="detail-badge">
                  <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                  {{ carga.veiculo }}
                </div>
                <div class="detail-badge">
                  <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                  {{ carga.produto }}
                </div>
                <div class="value-blurred">
                  R$ ****,** <!-- Oculto propositalmente -->
                </div>
              </div>

              <div class="freight-action">
                <button @click="requireLogin" class="btn-action w-full">
                  Ver Detalhes e Aceitar
                </button>
              </div>
            </div>
            
          </div>
          
          <div class="text-center mt-6">
             <button @click="requireLogin" class="text-brand-600 font-bold hover:underline text-sm flex items-center justify-center w-full">
                Exibir mais 142 cargas disponíveis
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
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
            <button @click="requireLogin" class="mt-8 btn-outline w-full">Quero Publicar Cargas</button>
          </div>
          
          <div class="audience-box bg-slate-800 border border-slate-700 text-white">
            <h2 class="audience-title text-emerald-400">Para Motoristas</h2>
            <h3 class="font-bold text-xl mb-6 text-white">Garantia Operacional</h3>
            <ul class="benefits-list dark">
              <li><strong>Fim do Frete Fantasma:</strong> Chega de rodar vazio. Todo frete publicado é real e tem fundos validados.</li>
              <li><strong>Acesso à Tecnologia:</strong> Oferecemos um hub integrado para rastreadores certificados.</li>
              <li><strong>Contrato Jurídico:</strong> O "boca a boca" acabou. Cada corrida gera um contrato resguardando seus direitos.</li>
            </ul>
            <button @click="requireLogin" class="mt-8 btn-primary w-full shadow-lg">Quero Encontrar Fretes</button>
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
        <p class="modal-text">Para garantir a segurança da nossa malha, visualizar os valores reais e aceitar cargas, você precisa estar autenticado.</p>
        
        <div class="flex flex-col gap-3 mt-8">
          <button @click="$router.push({ name: 'Login' })" class="btn-primary w-full py-3 text-lg">Fazer Login Agora</button>
          <button @click="$router.push({ name: 'RegisterMotorista' })" class="btn-text w-full py-2">Ainda não tenho conta</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

// Estado do Modal de Bloqueio
const isModalOpen = ref(false);
const requireLogin = () => isModalOpen.value = true;
const closeModal = () => isModalOpen.value = false;

// Mockups de Cargas para o Widget (Simulando feed ao vivo)
const cargasAoVivo = ref([
  { id: 1, origem: 'São Paulo, SP', destino: 'Rio de Janeiro, RJ', veiculo: 'Carreta LS', produto: 'Eletrônicos (Paletes)' },
  { id: 2, origem: 'Sorriso, MT', destino: 'Paranaguá, PR', veiculo: 'Bitrem', produto: 'Soja a Granel' },
  { id: 3, origem: 'Itatiba, SP', destino: 'Belo Horizonte, MG', veiculo: 'Truck (Baú)', produto: 'Autopeças' },
]);
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

/* ================= NAV ================= */
.navbar {
  position: sticky;
  top: 0;
  width: 100%;
  padding: 1rem 6%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
  z-index: 100;
}
.nav-links { gap: 2.5rem; }
.nav-links a { font-size: 0.9rem; font-weight: 600; color: #475569; transition: color 0.2s; }
.nav-links a:hover { color: var(--c-green); }
.nav-actions { display: flex; align-items: center; gap: 1.5rem; }
.btn-text { font-size: 0.9rem; font-weight: 700; color: #0f172a; cursor: pointer; transition: color 0.2s; }
.btn-text:hover { color: var(--c-brand); }
.btn-primary { background-color: var(--c-green); color: white; padding: 0.6rem 1.5rem; border-radius: 8px; font-size: 0.9rem; font-weight: 700; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(3, 93, 41, 0.2); }
.btn-primary:hover { background-color: var(--c-green-dark); }
.btn-outline { background-color: transparent; border: 2px solid var(--c-green); color: var(--c-green); padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 700; transition: all 0.2s; }
.btn-outline:hover { background-color: var(--c-green); color: white; }

/* ================= HERO ================= */
.hero-section {
  background: linear-gradient(135deg, var(--c-green) 0%, var(--c-green-dark) 100%);
  padding: 6rem 6% 12rem 6%; /* Padding bottom maior para caber o widget */
  color: white;
  text-align: center;
  position: relative;
}
.hero-content {
  max-width: 800px;
  margin: 0 auto;
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
  margin-bottom: 2rem;
  border: 1px solid rgba(255,255,255,0.2);
}
.title {
  font-size: clamp(2.5rem, 4vw, 4rem);
  font-weight: 900;
  line-height: 1.1;
  letter-spacing: -1.5px;
  margin-bottom: 1.5rem;
}
.title span { color: var(--c-brand); }
.description {
  font-size: 1.15rem;
  color: #cbd5e1;
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.6;
}

/* ================= WIDGET LOCALIZA STYLE ================= */
.widget-section {
  position: relative;
  margin-top: -8rem; /* Puxa o widget pra cima do hero */
  z-index: 10;
  padding: 0 4%;
}
.widget-container {
  max-width: 1000px;
  margin: 0 auto;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}
.widget-tabs {
  display: flex;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.tab {
  flex: 1;
  padding: 1.2rem;
  font-weight: 700;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.tab.active {
  background: white;
  color: var(--c-brand);
  border-bottom: 3px solid var(--c-brand);
}

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
  background: white;
}
.route-info { display: flex; align-items: center; justify-content: space-between; }
.route-point { display: flex; flex-direction: column; }
.route-point .city { font-weight: 800; color: #0f172a; font-size: 1.1rem; }
.route-point .label { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; }
.freight-details { display: flex; flex-direction: column; gap: 0.4rem; }
.detail-badge { display: flex; align-items: center; font-size: 0.85rem; color: #475569; font-weight: 600; }
.value-blurred {
  font-size: 1.2rem;
  font-weight: 900;
  color: #cbd5e1;
  text-shadow: 0 0 10px rgba(0,0,0,0.1);
  user-select: none;
}
.btn-action {
  background: #f1f5f9;
  color: var(--c-brand);
  border: 1px solid #cbd5e1;
  padding: 0.8rem;
  border-radius: 8px;
  font-weight: 800;
  transition: all 0.2s;
}
.btn-action:hover { background: var(--c-brand); color: white; border-color: var(--c-brand); }

/* ================= INFO SECTIONS ================= */
.info-section { padding: 6rem 0; }
.section-title { font-size: 2.2rem; font-weight: 800; color: #0f172a; letter-spacing: -1px; margin-bottom: 1rem; }
.section-subtitle { font-size: 1.1rem; color: #64748b; max-width: 600px; margin: 0 auto; }
.feature-card { background: white; border: 1px solid #e2e8f0; padding: 2.5rem; border-radius: 16px; transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
.feature-card:hover { transform: translateY(-5px); }
.icon-wrapper { font-size: 1.5rem; font-weight: 900; color: var(--c-green); background: #ecfdf5; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-bottom: 1.5rem; }
.feature-card h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 1rem; color: #0f172a; }
.feature-card p { font-size: 0.95rem; color: #64748b; line-height: 1.6; }

.audience-box { padding: 3rem; border-radius: 16px; }
.audience-title { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }
.benefits-list li { margin-bottom: 1rem; font-size: 0.95rem; color: #475569; position: relative; padding-left: 1.5rem; line-height: 1.5; }
.benefits-list li::before { content: '✓'; position: absolute; left: 0; color: var(--c-green); font-weight: 900; }
.benefits-list.dark li { color: #cbd5e1; }
.benefits-list.dark li::before { color: var(--c-brand); }

/* ================= MODAL ================= */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 999; display: flex; justify-content: center; align-items: center; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
.modal-overlay.active { opacity: 1; pointer-events: auto; }
.modal-box { background: white; padding: 3rem 2rem; border-radius: 20px; max-width: 450px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: translateY(20px); transition: transform 0.3s ease; }
.modal-overlay.active .modal-box { transform: translateY(0); }
.modal-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; color: #0f172a; }
.modal-text { font-size: 1rem; color: #64748b; line-height: 1.5; }

/* ================= RESPONSIVO ================= */
@media (max-width: 1024px) {
  .freight-card { grid-template-columns: 1fr; gap: 1rem; }
  .freight-card > div { border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; }
  .freight-card > div:last-child { border-bottom: none; padding-bottom: 0; }
  .route-info { justify-content: flex-start; gap: 1rem; }
  .route-arrow svg { transform: rotate(90deg); }
}

@media (max-width: 768px) {
  .hero-section { padding: 4rem 4% 10rem 4%; }
  .title { font-size: 2rem; }
  .widget-section { margin-top: -6rem; }
  .widget-content { padding: 1.5rem; }
  .tab { font-size: 0.85rem; padding: 1rem 0.5rem; text-align: center; }
  
  .nav-actions { gap: 0.8rem; }
  .btn-primary { padding: 0.5rem 1rem; font-size: 0.8rem; }
  
  .audience-box { padding: 2rem; }
}
</style>