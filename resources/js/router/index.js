import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    { path: '/', name: 'Welcome', component: () => import('../views/Welcome.vue') },
    { path: '/login', name: 'Login', component: () => import('../views/Login.vue') },
    { path: '/reset-password', name: 'ResetPassword', component: () => import('../views/ResetPassword.vue'), meta: { title: 'Redefinir Senha' } },
    { path: '/register/embarcador', name: 'RegisterEmbarcador', component: () => import('../views/RegisterEmbarcador.vue') },
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
            { path: 'rastreamento/:id', name: 'EmbarcadorRastreamento', component: () => import('../views/Embarcador/RastreamentoCarga.vue'), meta: { title: 'Auditoria de Rota' } },
            { path: 'suporte', name: 'EmbarcadorMeusChamados', component: () => import('../views/Embarcador/MeusChamados.vue'), meta: { title: 'Central de Suporte (SAC)' } },
            { path: 'faturas', name: 'EmbarcadorFaturas', component: () => import('../views/Embarcador/Faturas.vue'), meta: { title: 'Minhas Faturas' } },
            { path: 'perfil', name: 'EmbarcadorPerfil', component: () => import('../views/Embarcador/Perfil.vue'), meta: { title: 'Minha Conta' } },
            
            // --- HUB EMBARCADOR ---
            { path: 'faq', name: 'EmbarcadorFaq', component: () => import('../views/Hub/FaqView.vue'), meta: { title: 'Central de Ajuda (FAQ)' } },
            { path: 'loja', name: 'EmbarcadorLoja', component: () => import('../views/Hub/LojaView.vue'), meta: { title: 'Loja VIWE SyS' } },
            { path: 'voucher', name: 'EmbarcadorVoucher', component: () => import('../views/Hub/VoucherView.vue'), meta: { title: 'Gestão de Vouchers' } },
            { path: 'parceiros', name: 'EmbarcadorParceiros', component: () => import('../views/Hub/ParceirosView.vue'), meta: { title: 'Parceiros Estratégicos' } }
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
            { path: 'rastreamento/:id', name: 'RastreadorFrete', component: () => import('../views/Motorista/RastreadorFrete.vue'), meta: { title: 'Painel de Viagem Ativa' } },
            { path: 'suporte', name: 'MotoristaMeusChamados', component: () => import('../views/Motorista/MeusChamados.vue'), meta: { title: 'Central de Suporte (SAC)' } },
            { path: 'perfil', name: 'MotoristaPerfil', component: () => import('../views/Motorista/Perfil.vue'), meta: { title: 'Minha Conta' } },
            
            // --- HUB MOTORISTA ---
            { path: 'faq', name: 'MotoristaFaq', component: () => import('../views/Hub/FaqView.vue'), meta: { title: 'Central de Ajuda (FAQ)' } },
            { path: 'loja', name: 'MotoristaLoja', component: () => import('../views/Hub/LojaView.vue'), meta: { title: 'Loja VIWE SyS' } },
            { path: 'voucher', name: 'MotoristaVoucher', component: () => import('../views/Hub/VoucherView.vue'), meta: { title: 'Meus Vouchers' } },
            { path: 'parceiros', name: 'MotoristaParceiros', component: () => import('../views/Hub/ParceirosView.vue'), meta: { title: 'Parceiros Estratégicos' } }
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
            { path: 'dashboard', name: 'AdminDashboard', component: () => import('../views/Admin/Dashboard.vue'), meta: { title: 'Centro de Comando' } },
            { path: 'suporte', name: 'AdminSuporte', component: () => import('../views/Admin/MesaOperacoes.vue'), meta: { title: 'Mesa de Operações (SAC)' } },
            { path: 'fretes', name: 'AdminFretes', component: () => import('../views/Admin/MuralFretes.vue'), meta: { title: 'Mural de Fretes' } },
            { path: 'disputas', name: 'AdminDisputas', component: () => import('../views/Admin/Disputas.vue'), meta: { title: 'Resolução de Disputas' } },
            { path: 'auditoria', name: 'AdminAuditoria', component: () => import('../views/Admin/Kyc.vue'), meta: { title: 'Auditoria KYC' } },
            { path: 'motoristas', name: 'AdminMotoristas', component: () => import('../views/Admin/BaseMotoristas.vue'), meta: { title: 'Base de Motoristas' } },
            { path: 'embarcadores', name: 'AdminEmbarcadores', component: () => import('../views/Admin/BaseEmbarcadores.vue'), meta: { title: 'Base de Embarcadores' } },
            { path: 'extrato', name: 'AdminExtrato', component: () => import('../views/Admin/ExtratoTaxas.vue'), meta: { title: 'Extrato & Taxas' } },
            { path: 'faturamento', name: 'AdminFaturamento', component: () => import('../views/Admin/Faturamento.vue'), meta: { title: 'Faturamento' } },
            { path: 'staff', name: 'AdminStaff', component: () => import('../views/Admin/Staff.vue'), meta: { title: 'Staff & Permissões' } },
            { path: 'config', name: 'AdminConfig', component: () => import('../views/Admin/VariaveisGlobais.vue'), meta: { title: 'Variáveis Globais' } }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() { return { top: 0 }; }
});

// ==========================================
// ESCUDO DE NAVEGAÇÃO (ZERO TRUST FRONTEND)
// ==========================================
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // 1. Hidratação Atômica de Estado (Prevenção de F5 Branco)
    // Se há indício de sessão na máquina, mas a RAM do Pinia está limpa, faz o fetch ANTES de renderizar a tela.
    const hasLocalSession = localStorage.getItem('user');
    if (!authStore.user && hasLocalSession) {
        await authStore.fetchUser();
    }

    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    // As roles agora podem vir da rota atual ou da rota "pai" (matched)
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

    // 2. Interceptação Nível 1: Não Autenticado
    if (requiresAuth && !authStore.isAuthenticated) {
        console.warn('[Security] Acesso bloqueado: Rejeitado na Borda (Não autenticado).');
        return next({ name: 'Login' });
    }

    // 3. Interceptação Nível 2: Violação de Papel (Role-Based Access Control)
    if (requiresAuth && requiredRoles.length > 0 && !requiredRoles.includes(userRole)) {
        console.error(`[Security] Violação de RBAC. Perfil '${userRole}' tentou acesso à rota restrita.`);
        authStore.clearAuth(); // Corta o mal pela raiz e expulsa da sessão
        return next({ name: 'Login' });
    }

    // 4. Redirecionamento de rotas de visitante (Login/Reset) para usuários já logados
    const guestRoutes = ['/login', '/reset-password'];
    if (guestRoutes.includes(to.path) && authStore.isAuthenticated && authStore.user) {
        const staffRoles = ['admin', 'manager', 'compliance', 'suporte_n1'];
        if (staffRoles.includes(userRole)) {
            return userRole === 'suporte_n1' ? next({ name: 'AdminSuporte' }) : next({ name: 'AdminDashboard' });
        }
        return next(`/${userRole}/painel`); 
    }

    // 5. Acesso Liberado
    next();
});

export default router;