<template>
  <div class="landing-page scroll-smooth">
    
    <nav class="navbar">
      <div class="nav-brand" style="display: flex; align-items: center; gap: 8px;">
        <svg width="32" height="32" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M50 10C27.9086 10 10 27.9086 10 50C10 61.0457 14.4772 71.0457 21.8016 78.3701L50 100L78.1984 78.3701C85.5228 71.0457 90 61.0457 90 50C90 27.9086 72.0914 10 50 10Z" fill="#F28C28"/>
          <circle cx="50" cy="40" r="15" fill="#2F5C3E"/>
        </svg>
        <span style="font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; display: flex; align-items: baseline;">
          <span style="color: #2F5C3E;">123</span><span style="color: #F28C28;">Fretei</span>
        </span>
      </div>
      
      <div class="nav-links">
        <a href="#tecnologia">Arquitetura de Dados</a>
        <a href="#seguranca">Auditoria & Risco Zero</a>
        <a href="#parceiros">Integrações</a>
      </div>
      <div class="nav-actions">
        <router-link :to="{ name: 'Login' }" class="btn-text">Acessar Hub</router-link>
        <router-link :to="{ name: 'RegisterEmbarcador' }" class="btn-primary">Homologar Conta</router-link>
      </div>
    </nav>

    <div id="vanta-canvas"></div>

    <section class="hero">
      <div class="hero-content">
        <span class="tagline">Arquitetura Logística Zero Trust</span>
        <h1 class="title">A Primeira Malha de Fretes 100% <span>Auditável do Brasil.</span></h1>
        <p class="description">Esqueça a informalidade. Construímos uma infraestrutura modular escalável para conectar embarcadores e transportadores autônomos sob um ecossistema rigoroso de biometria, cruzamento de dados fiscais e segurança jurídica.</p>
        <div class="hero-buttons">
          <router-link :to="{ name: 'RegisterEmbarcador' }" class="btn-large primary">Para Embarcadores</router-link>
          <router-link :to="{ name: 'RegisterMotorista' }" class="btn-large secondary bg-white/50 backdrop-blur-sm">Para Motoristas</router-link>
        </div>
      </div>
      <div class="hero-image-wrapper"></div>
    </section>

    <section id="tecnologia" class="architecture">
      <h2 class="section-heading">Tecnologia desenvolvida para escalar.</h2>
      <p class="section-sub">Iniciamos nossa operação projetados para suportar o tráfego pesado de dados. Nossa meta arquitetural absorve de 1.300 parceiros iniciais até o volume de 2 milhões de usuários sem gargalos operacionais.</p>
      
      <div class="editorial-grid">
        <div class="editorial-block">
          <div class="block-num">01</div>
          <h3 class="block-title">Emissão Fiscal Automatizada</h3>
          <p class="block-text">Abandone sistemas paralelos. O portal atua como emissor nativo de CT-e e Manifesto, além de gerar contratos de prestação de serviço assinados digitalmente a cada match consolidado.</p>
        </div>
        <div class="editorial-block">
          <div class="block-num">02</div>
          <h3 class="block-title">Malha Fina Cadastral (Brasil API)</h3>
          <p class="block-text">Todo cadastro exige documentação veicular e CNH, processados em background via Brasil API. Dados divergentes são sumariamente bloqueados antes do primeiro acesso.</p>
        </div>
        <div class="editorial-block">
          <div class="block-num">03</div>
          <h3 class="block-title">Score de Confiabilidade</h3>
          <p class="block-text">Operamos um sistema implacável de ranqueamento. O histórico de pontualidade e compliance financeiro gera uma nota pública que define quem acessa os melhores fretes.</p>
        </div>
      </div>
    </section>

    <section id="seguranca" class="dual-proposition">
      <div class="dual-grid">
        <div class="target-audience">
          <h2 class="role-title">Blindagem Corporativa (Embarcadores)</h2>
          <ul class="benefits-list">
            <li><strong>Filtro Biométrico Avançado:</strong> A validação de identidade não se baseia apenas em papel. Exigimos reconhecimento facial ativo do motorista.</li>
            <li><strong>Gerenciamento de Risco:</strong> Integramos via API com plataformas de inteligência, anulando a exposição a golpistas e desvios de carga.</li>
            <li><strong>Tripla Cobertura de Seguros:</strong> O sistema possibilita o acionamento de apólices integradas no ato da contratação.</li>
          </ul>
        </div>
        <div class="target-audience">
          <h2 class="role-title">Garantia Operacional (Autônomos)</h2>
          <ul class="benefits-list">
            <li><strong>Fim do Frete Fantasma:</strong> Chega de rodar vazio por falsas promessas. O frete publicado é real e com lastro fiscal.</li>
            <li><strong>Acesso à Tecnologia:</strong> Não tem rastreador? Oferecemos um hub integrado para equipamentos certificados.</li>
            <li><strong>Contrato Jurídico:</strong> O "boca a boca" acabou. Cada corrida gera um contrato digital imediato, resguardando seus direitos.</li>
          </ul>
        </div>
      </div>
    </section>

    <section id="parceiros" class="faq-section">
      <div class="faq-container">
        <h2 class="section-heading text-center" style="text-align: center;">A Engenharia por trás da Confiança.</h2>
        <div class="faq-grid mt-10">
          <div class="faq-box">
            <h4 class="faq-q">Como a plataforma impossibilita a ação de golpistas?</h4>
            <p class="faq-a">Implementamos uma política rigorosa de "Zero Trust". O cruzamento de dados não é manual; nossa infraestrutura interroga bases governamentais e privadas simultaneamente.</p>
          </div>
          <div class="faq-box">
            <h4 class="faq-q">O portal suportará o volume de grandes transportadoras?</h4>
            <p class="faq-a">Sim. Desenvolvemos o sistema de forma modular e escalável, sem latência nas requisições de match.</p>
          </div>
          <div class="faq-box">
            <h4 class="faq-q">A emissão de documentos tem custo extra?</h4>
            <p class="faq-a">O CT-e, o MDF-e e a assinatura com certificado digital são nativos da operação e gerados automaticamente.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="cta-section">
      <h2 class="cta-title">A informalidade logística tem os dias contados.</h2>
      <p class="cta-desc">Junte-se ao ecossistema de fretes mais blindado, auditável e eficiente do mercado corporativo brasileiro.</p>
      <button @click="openModal" class="btn-large" style="background: var(--c-brand); color: white; border: none;">Solicitar Acesso Early Bird</button>
    </section>

    <footer class="footer">
      <div class="nav-brand">
        <span style="font-size: 1.5rem; font-weight: 900; letter-spacing: -1px;">
          <span style="color: #2F5C3E;">123</span><span style="color: #F28C28;">Fretei</span>
        </span>
      </div>
      <p class="footer-text">&copy; 2026 123FRETEI. Engenharia de Software e Logística Integrada.</p>
    </footer>

    <div class="modal-overlay" :class="{ 'active': isModalOpen }" @click.self="closeModal">
      <div class="modal-box">
        <h3 class="modal-title">Infraestrutura em Homologação</h3>
        <p class="modal-text">Estamos finalizando os testes de estresse em nossos nós de integração e biometria para garantir Risco Zero. O lançamento transformará a forma como o Brasil transporta riquezas.</p>
        <button @click="closeModal" class="modal-btn">Compreendido</button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

// A importação do logo defeituoso foi permanentemente removida

const isModalOpen = ref(false);
const openModal = () => isModalOpen.value = true;
const closeModal = () => isModalOpen.value = false;

let vantaEffect = null;

onMounted(() => {
  const threeScript = document.createElement('script');
  threeScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js';
  
  const vantaScript = document.createElement('script');
  vantaScript.src = 'https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js';

  threeScript.onload = () => {
    document.head.appendChild(vantaScript);
  };

  vantaScript.onload = () => {
    if (window.VANTA) {
      vantaEffect = window.VANTA.NET({
        el: "#vanta-canvas",
        mouseControls: false,
        touchControls: false,
        gyroControls: false,
        minHeight: 200.00,
        minWidth: 200.00,
        scale: 1.00,
        scaleMobile: 1.00,
        color: 0xff5500,
        backgroundColor: 0xffffff,
        points: 10.00,
        maxDistance: 22.00,
        spacing: 20.00,
        showDots: true
      });
    }
  };

  document.head.appendChild(threeScript);
});

onBeforeUnmount(() => {
  if (vantaEffect) {
    vantaEffect.destroy();
  }
});
</script>

<style scoped>
.landing-page {
  --c-bg-pure: #ffffff;
  --c-bg-alt: #f8f9fa;
  --c-text-main: #0f172a;
  --c-text-muted: #64748b;
  --c-brand: #ff5500;
  --c-brand-hover: #e64d00;
  --c-border: #e2e8f0;
  
  font-family: 'Inter', sans-serif;
  background-color: var(--c-bg-pure);
  color: var(--c-text-main);
  overflow-x: hidden;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

.navbar {
  position: fixed;
  top: 0;
  width: 100%;
  padding: 1.2rem 6%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  z-index: 100;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}

.nav-links { display: flex; gap: 2.5rem; }
.nav-links a { text-decoration: none; font-size: 0.85rem; font-weight: 600; color: var(--c-text-muted); transition: color 0.2s; }
.nav-links a:hover { color: var(--c-text-main); }
.nav-actions { display: flex; align-items: center; gap: 1.5rem; }

.btn-text { text-decoration: none; font-size: 0.85rem; font-weight: 700; color: var(--c-text-main); cursor: pointer; }
.btn-primary { text-decoration: none; background-color: var(--c-text-main); color: var(--c-bg-pure); padding: 0.7rem 1.5rem; border-radius: 6px; font-size: 0.85rem; font-weight: 700; transition: background-color 0.2s; cursor: pointer; }
.btn-primary:hover { background-color: #000000; }

#vanta-canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100vh; z-index: 0; opacity: 0.12; pointer-events: none; }

.hero { position: relative; min-height: 100vh; display: flex; align-items: center; padding: 0 6%; z-index: 1; }
.hero-content { flex: 1; max-width: 750px; padding-top: 5rem; }
.tagline { display: inline-block; font-size: 0.75rem; font-weight: 700; background: var(--c-bg-alt); border: 1px solid var(--c-border); padding: 0.4rem 1rem; border-radius: 50px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2rem; }
.title { font-size: clamp(3rem, 5vw, 4.2rem); font-weight: 900; line-height: 1.05; letter-spacing: -2px; margin-bottom: 1.5rem; color: var(--c-text-main); }
.title span { color: var(--c-brand); }
.description { font-size: 1.2rem; font-weight: 400; color: var(--c-text-muted); max-width: 650px; margin-bottom: 3rem; line-height: 1.7; }
.hero-buttons { display: flex; gap: 1rem; }
.btn-large { text-decoration: none; padding: 1rem 2rem; border-radius: 6px; font-size: 0.95rem; font-weight: 700; transition: all 0.2s; text-align: center; cursor: pointer; }
.btn-large.primary { background-color: var(--c-text-main); color: var(--c-bg-pure); }
.btn-large.primary:hover { background-color: #000000; }
.btn-large.secondary { background-color: transparent; color: var(--c-text-main); border: 1px solid var(--c-border); }
.btn-large.secondary:hover { background-color: var(--c-bg-alt); }

.hero-image-wrapper { flex: 1; position: absolute; right: 0; top: 0; width: 45%; height: 100vh; background-image: linear-gradient(to right, transparent, #f1f5f9); background-size: cover; background-position: center left; mask-image: linear-gradient(to right, transparent 0%, black 30%); -webkit-mask-image: linear-gradient(to right, transparent 0%, black 30%); z-index: -1; }

.architecture { position: relative; padding: 8rem 6%; background-color: var(--c-bg-pure); z-index: 2; border-top: 1px solid var(--c-border); }
.section-heading { font-size: 2.5rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 1rem; }
.section-sub { font-size: 1.15rem; color: var(--c-text-muted); margin-bottom: 5rem; max-width: 650px; }
.editorial-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4rem; }
.editorial-block { display: flex; flex-direction: column; }
.block-num { font-size: 1.5rem; font-weight: 900; background: var(--c-bg-alt); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--c-border); margin-bottom: 1.5rem; }
.block-title { font-size: 1.3rem; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 1rem; }
.block-text { font-size: 0.95rem; color: var(--c-text-muted); line-height: 1.7; }

.dual-proposition { position: relative; padding: 8rem 6%; background-color: var(--c-bg-alt); z-index: 2; border-top: 1px solid var(--c-border); }
.dual-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5rem; }
.target-audience { background: var(--c-bg-pure); padding: 3rem; border-radius: 16px; border: 1px solid var(--c-border); box-shadow: 0 10px 40px rgba(0,0,0,0.02); }
.role-title { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 2rem; border-bottom: 1px solid var(--c-border); padding-bottom: 1.5rem; }
.benefits-list { list-style: none; padding: 0; }
.benefits-list li { font-size: 0.95rem; color: var(--c-text-muted); margin-bottom: 1.5rem; position: relative; padding-left: 1.5rem; }
.benefits-list li::before { content: '—'; position: absolute; left: 0; color: var(--c-brand); font-weight: 900; }
.benefits-list strong { color: var(--c-text-main); font-weight: 700; }

.faq-section { padding: 8rem 6%; background-color: var(--c-bg-pure); border-top: 1px solid var(--c-border); }
.faq-container { max-width: 900px; margin: 0 auto; }
.faq-grid { display: flex; flex-direction: column; gap: 1.5rem; }
.faq-box { background: var(--c-bg-alt); border: 1px solid var(--c-border); padding: 2.5rem; border-radius: 12px; }
.faq-q { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.8rem; letter-spacing: -0.3px; }
.faq-a { font-size: 0.95rem; color: var(--c-text-muted); line-height: 1.7; }

.cta-section { padding: 6rem 6%; background-color: var(--c-text-main); text-align: center; }
.cta-title { font-size: 2.5rem; font-weight: 800; color: var(--c-bg-pure); letter-spacing: -1px; margin-bottom: 1rem; }
.cta-desc { font-size: 1.15rem; color: #94a3b8; margin-bottom: 3rem; max-width: 600px; margin-left: auto; margin-right: auto; }
.footer { padding: 4rem 6%; background-color: var(--c-bg-pure); display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--c-border); }
.footer-text { font-size: 0.85rem; color: var(--c-text-muted); }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 999; display: flex; justify-content: center; align-items: center; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
.modal-overlay.active { opacity: 1; pointer-events: auto; }
.modal-box { background: var(--c-bg-pure); padding: 3rem; border-radius: 16px; max-width: 450px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: translateY(20px); transition: transform 0.3s ease; border: 1px solid var(--c-border); }
.modal-overlay.active .modal-box { transform: translateY(0); }
.modal-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; letter-spacing: -0.5px;}
.modal-text { font-size: 1rem; color: var(--c-text-muted); margin-bottom: 2rem; line-height: 1.6;}
.modal-btn { background: var(--c-text-main); color: var(--c-bg-pure); border: none; padding: 0.8rem 2rem; border-radius: 6px; font-weight: 700; cursor: pointer; transition: background 0.2s; width: 100%; font-size: 1rem; }
.modal-btn:hover { background: #000; }

@media (max-width: 1024px) {
  .hero-image-wrapper { display: none; }
  .hero-content { max-width: 100%; padding-top: 8rem; }
  .nav-links { display: none; }
  .hero-buttons { flex-direction: column; }
  .dual-grid, .editorial-grid { grid-template-columns: 1fr; gap: 2rem; }
  .architecture, .dual-proposition, .faq-section { padding: 5rem 6%; }
}
</style>