import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    { path: '/', name: 'Welcome', component: () => import('../views/Welcome.vue') },
    { path: '/login', name: 'Login', component: () => import('../views/Login.vue') },
    { path: '/reset-password', name: 'ResetPassword', component: () => import('../views/ResetPassword.vue'), meta: { title: 'Redefinir Senha' } },
    
    // 🔥 IMPORTAÇÕES ASSÍNCRONAS CORRETAS (Evita tela branca)
    { path: '/register', name: 'ChooseProfile', component: () => import('../views/ChooseProfile.vue') },
    
    // 👇 A ROTA DO EMBARCADOR VOLTOU AQUI (Apontando para o EmbarcadorRegister.vue)
    { path: '/register/embarcador', name: 'RegisterEmbarcador', component: () => import('../views/EmbarcadorRegister.vue') },
    
    { path: '/register/motorista', name: 'RegisterMotorista', component: () => import('../views/RegisterMotorista.vue') },

    // ==========================================
    // ROTAS DO EMBARCADOR
    // ==========================================
    {
        path: '/embarcador',
        component: () => import('../Layouts/EmbarcadorLayout.vue'),
        meta: { requiresAuth: true, role: ['embarcador'] },
        children: [
            { path: '', redirect: '/embarcador/painel' },
            { path: 'painel', name: 'EmbarcadorDashboard', component: () => import('../views/Embarcador/Dashboard.vue'), meta: { title: 'Mural de Cargas' } },
            { path: 'nova-carga', name: 'EmbarcadorNovaCarga', component: () => import('../views/Embarcador/CriarCarga.vue'), meta: { title: 'Publicar Novo Frete' } },
            { path: 'editar-carga/:id', name: 'EmbarcadorEditarCarga', component: () => import('../views/Embarcador/EditarCarga.vue'), meta: { title: 'Editar Frete' } },
            { path: 'suporte', name: 'EmbarcadorMeusChamados', component: () => import('../views/Embarcador/MeusChamados.vue'), meta: { title: 'Central de Suporte (SAC)' } },
            { path: 'faturas', name: 'EmbarcadorFaturas', component: () => import('../views/Embarcador/Faturas.vue'), meta: { title: 'Minhas Faturas' } },
            { path: 'perfil', name: 'EmbarcadorPerfil', component: () => import('../views/Embarcador/Perfil.vue'), meta: { title: 'Minha Conta' } },
            { path: 'locais', name: 'EmbarcadorLocais', component: () => import('../views/Embarcador/LocaisOperacionais.vue'), meta: { title: 'Locais Operacionais' } },
            { path: 'faq', name: 'EmbarcadorFaq', component: () => import('../views/Hub/FaqView.vue'), meta: { title: 'Central de Ajuda (FAQ)' } },
            { path: 'loja', name: 'EmbarcadorLoja', component: () => import('../views/Hub/LojaView.vue'), meta: { title: 'Loja' } },
            { path: 'voucher', name: 'EmbarcadorVoucher', component: () => import('../views/Hub/VoucherView.vue'), meta: { title: 'Gestão de Vouchers' } },
            { path: 'parceiros', name: 'EmbarcadorParceiros', component: () => import('../views/Hub/ParceirosView.vue'), meta: { title: 'Parceiros Estratégicos' } },
            { path: 'rastreamento/:id', name: 'EmbarcadorRastreamento', component: () => import('../views/Embarcador/Rastreamento.vue'), meta: { title: 'Rastreamento' } }
        ]
    },

    // ==========================================
    // ROTAS DO MOTORISTA
    // ==========================================
    {
        path: '/motorista',
        component: () => import('../Layouts/MotoristaLayout.vue'),
        meta: { requiresAuth: true, role: ['motorista'] },
        children: [
            { path: '', redirect: '/motorista/painel' },
            { path: 'painel', name: 'MotoristaMural', component: () => import('../views/Motorista/Dashboard.vue'), meta: { title: 'Mural de Fretes' } },
            { path: 'minhas-cargas', name: 'MotoristaMeusFretes', component: () => import('../views/Motorista/MeusFretes.vue'), meta: { title: 'Meus Fretes Alocados' } },
            { path: 'carteira', name: 'MotoristaCarteira', component: () => import('../views/Motorista/Carteira.vue'), meta: { title: 'Minha Carteira' } },
            { path: 'suporte', name: 'MotoristaMeusChamados', component: () => import('../views/Motorista/MeusChamados.vue'), meta: { title: 'Central de Suporte (SAC)' } },
            { path: 'perfil', name: 'MotoristaPerfil', component: () => import('../views/Motorista/Perfil.vue'), meta: { title: 'Minha Conta' } },
            { path: 'faq', name: 'MotoristaFaq', component: () => import('../views/Hub/FaqView.vue'), meta: { title: 'Central de Ajuda (FAQ)' } },
            { path: 'loja', name: 'MotoristaLoja', component: () => import('../views/Hub/LojaView.vue'), meta: { title: 'Loja' } },
            { path: 'voucher', name: 'MotoristaVoucher', component: () => import('../views/Hub/VoucherView.vue'), meta: { title: 'Meus Vouchers' } },
            { path: 'parceiros', name: 'MotoristaParceiros', component: () => import('../views/Hub/ParceirosView.vue'), meta: { title: 'Parceiros Estratégicos' } },
            
            // 👇 GATILHO PASSIVO / UPSELL DA IZA SEGURADORA (Components com "C" maiúsculo)
            { path: 'seguros', name: 'MotoristaSeguros', component: () => import('../Components/SeguroIzaPanel.vue'), meta: { title: 'Seguros e Benefícios' } }
        ]
    },

    // ==========================================
    // ROTAS DE ADMINISTRAÇÃO (BACKOFFICE)
    // ==========================================
    {
        path: '/admin',
        component: () => import('../Layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, role: ['admin', 'manager', 'compliance', 'suporte_n1'] },
        children: [
            { path: '', redirect: '/admin/dashboard' },
            { path: 'dashboard', name: 'AdminDashboard', component: () => import('../views/Admin/Dashboard.vue'), meta: { title: 'Centro de Comando', role: ['admin', 'manager'] } },
            { path: 'suporte', name: 'AdminSuporte', component: () => import('../views/Admin/MesaOperacoes.vue'), meta: { title: 'Mesa de Operações (SAC)', role: ['admin', 'manager', 'compliance', 'suporte_n1'] } },
            { path: 'fretes', name: 'AdminFretes', component: () => import('../views/Admin/MuralFretes.vue'), meta: { title: 'Mural de Fretes', role: ['admin', 'manager'] } },
            { path: 'historico-fretes', name: 'AdminHistoricoFretes', component: () => import('../views/Admin/HistoricoFretes.vue'), meta: { title: 'Arquivo Morto (Auditoria)', role: ['admin', 'manager', 'compliance'] } },
            { path: 'disputas', name: 'AdminDisputas', component: () => import('../views/Admin/Disputas.vue'), meta: { title: 'Resolução de Disputas', role: ['admin', 'manager', 'compliance'] } },
            { path: 'auditoria', name: 'AdminAuditoria', component: () => import('../views/Admin/Kyc.vue'), meta: { title: 'Auditoria KYC', role: ['admin', 'compliance'] } },
            { path: 'motoristas', name: 'AdminMotoristas', component: () => import('../views/Admin/BaseMotoristas.vue'), meta: { title: 'Base de Motoristas', role: ['admin'] } },
            { path: 'embarcadores', name: 'AdminEmbarcadores', component: () => import('../views/Admin/BaseEmbarcadores.vue'), meta: { title: 'Base de Embarcadores', role: ['admin'] } },
            { path: 'parceiros', name: 'AdminParceiros', component: () => import('../views/Admin/Parceiros.vue'), meta: { title: 'Rede de Parceiros (CMS)', role: ['admin'] } },
            { path: 'extrato', name: 'AdminExtrato', component: () => import('../views/Admin/ExtratoTaxas.vue'), meta: { title: 'Extrato & Taxas', role: ['admin'] } },
            { path: 'faturamento', name: 'AdminFaturamento', component: () => import('../views/Admin/Faturamento.vue'), meta: { title: 'Faturamento', role: ['admin'] } },
            { path: 'staff', name: 'AdminStaff', component: () => import('../views/Admin/Staff.vue'), meta: { title: 'Staff & Permissões', role: ['admin'] } },
            { path: 'config', name: 'AdminConfig', component: () => import('../views/Admin/VariaveisGlobais.vue'), meta: { title: 'Variáveis Globais', role: ['admin'] } },
            { path: 'parceiros-api', name: 'AdminParceirosApi', component: () => import('../views/Admin/ParceirosApi.vue'), meta: { title: 'Integrações & APIs', role: ['admin'] } }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() { return { top: 0 }; }
});

// ==========================================
// ESCUDO DE NAVEGAÇÃO (ZERO TRUST FRONTEND - V5)
// ==========================================
router.beforeEach(async (to, from) => {
    const authStore = useAuthStore();
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);

    if (requiresAuth && !authStore.isAuthenticated) {
        try {
            await authStore.fetchUser();
        } catch (error) {
            authStore.clearAuth();
            return { name: 'Login' };
        }
    }

    let requiredRoles = [];
    if (to.meta.role) {
        requiredRoles = Array.isArray(to.meta.role) ? to.meta.role : [to.meta.role];
    } else {
        const parentWithRole = to.matched.slice().reverse().find(record => record.meta && record.meta.role);
        if (parentWithRole) {
            requiredRoles = Array.isArray(parentWithRole.meta.role) ? parentWithRole.meta.role : [parentWithRole.meta.role];
        }
    }

    const userRole = authStore.user?.role?.slug;

    if (requiresAuth && !authStore.isAuthenticated) {
        console.warn('[Security] Acesso bloqueado: Rejeitado na Borda.');
        return { name: 'Login' };
    }

    if (requiresAuth && requiredRoles.length > 0 && !requiredRoles.includes(userRole)) {
        console.error(`[Security] Violação de RBAC. Perfil '${userRole}' tentou acesso à rota restrita.`);
        authStore.clearAuth(); 
        return { name: 'Login' };
    }

    const guestRoutes = ['/login', '/reset-password', '/register', '/register/embarcador', '/register/motorista'];
    
    // =======================================================
    // CORREÇÃO: Tratamento blindado para usuário sem cargo (Google)
    // =======================================================
    if (guestRoutes.includes(to.path) && authStore.isAuthenticated && authStore.user) {
        
        // Garante que safeRole não é a palavra 'undefined'
        const safeRole = userRole ? String(userRole).trim().toLowerCase() : '';
        const staffRoles = ['admin', 'manager', 'compliance', 'suporte_n1'];

        // 1. USUÁRIO NOVO (Sem cargo definido)
        if (!safeRole || safeRole === 'undefined' || safeRole === 'null') {
            // Se ele estiver tentando ir para qualquer página de registro, deixa passar.
            if (to.path.startsWith('/register')) {
                return true; 
            }
            // Se tentar ir para a home/login, joga ele para escolher o perfil
            return { name: 'ChooseProfile' }; 
        }

        // 2. USUÁRIO DA EQUIPE (Admin/Suporte)
        if (staffRoles.includes(safeRole)) {
            return safeRole === 'suporte_n1' ? { name: 'AdminSuporte' } : { name: 'AdminDashboard' };
        }
        
        // 3. USUÁRIO COMUM (Motorista ou Embarcador)
        return { path: `/${safeRole}/painel` }; 
    }

    return true; 
});

export default router;