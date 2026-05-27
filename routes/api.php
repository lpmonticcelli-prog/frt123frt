<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Embarcador\CargaController as EmbarcadorCargaController;
use App\Http\Controllers\Api\V1\Embarcador\FaturaController;
use App\Http\Controllers\Api\V1\Embarcador\PerfilController as EmbarcadorPerfilController;
use App\Http\Controllers\Api\V1\Embarcador\AuditoriaController; 
use App\Http\Controllers\Api\V1\Motorista\CargaController as MotoristaCargaController;
use App\Http\Controllers\Api\V1\Motorista\PerfilController as MotoristaPerfilController;
use App\Http\Controllers\Api\V1\Motorista\CarteiraController;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\ParceiroController;
use App\Http\Controllers\Api\V1\Admin\FaturamentoController as AdminFaturamentoController;
use App\Http\Controllers\Api\V1\Support\TicketController;
use App\Http\Controllers\Api\V1\Support\FaqController;
use App\Http\Controllers\Api\V1\Webhooks\PefWebhookController; 
use App\Http\Controllers\Api\V1\Webhooks\TransatWebhookController;

Route::prefix('v1')->group(function () {

    // =========================================================
    // AUTENTICAÇÃO E IDENTIDADE
    // =========================================================
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    
    // ZT-DEFENSE: Proteção L7 alinhada ao rate limit da API da ReceitaWS (3 requisições / 1 minuto por IP)
    Route::post('/register/embarcador', [AuthController::class, 'registerEmbarcador'])->middleware('throttle:3,1');
    
    // ZT-DEFENSE: Mitigação contra Botnets de cadastro e esgotamento de banco
    Route::post('/register/motorista', [AuthController::class, 'registerMotorista'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // =========================================================
        // DOMÍNIO: SUPORTE, FAQ & HUB (Transversal)
        // =========================================================
        Route::get('/suporte/faqs', [FaqController::class, 'index']);
        Route::get('/suporte/tickets', [TicketController::class, 'index']);
        Route::post('/suporte/tickets', [TicketController::class, 'store'])->middleware('throttle:5,1');
        Route::get('/suporte/tickets/{ticket}', [TicketController::class, 'show']);
        Route::post('/suporte/tickets/{ticket}/mensagens', [TicketController::class, 'reply'])->middleware('throttle:15,1');
        
        // 🔒 ZERO TRUST: Rotas do Hub de Parceiros (Blindadas contra Botnets)
        Route::get('/hub/parceiros', [ParceiroController::class, 'listarPorPublico'])->middleware('throttle:120,1');
        Route::post('/hub/parceiros/{parceiro}/clique', [ParceiroController::class, 'registrarClique'])->middleware('throttle:10,1');
        Route::post('/hub/parceiros/{parceiro}/conversao', [ParceiroController::class, 'registrarConversao'])->middleware('throttle:10,1');

        // =========================================================
        // DOMÍNIO: EMBARCADOR
        // =========================================================
        Route::middleware('ability:embarcador')->prefix('embarcador')->group(function () {
            Route::get('perfil', [EmbarcadorPerfilController::class, 'show']);
            Route::put('perfil', [EmbarcadorPerfilController::class, 'update']);
            
            Route::apiResource('cargas', EmbarcadorCargaController::class);
            Route::post('cargas/{carga}/candidaturas/aprovar', [EmbarcadorCargaController::class, 'aprovarCandidato'])->middleware('throttle:10,1');
            Route::post('cargas/{carga}/avaliar', [EmbarcadorCargaController::class, 'avaliarEFinalizarEntrega'])->middleware('throttle:5,1');
            Route::post('cargas/{carga}/disputa', [EmbarcadorCargaController::class, 'abrirDisputa'])->middleware('throttle:5,1');
            
            Route::get('cargas/{carga}/chat', [EmbarcadorCargaController::class, 'getChat']);
            Route::post('cargas/{carga}/chat', [EmbarcadorCargaController::class, 'storeChat'])->middleware('throttle:20,1');

            Route::get('faturas', [FaturaController::class, 'index']);
            Route::get('faturas/{fatura}', [FaturaController::class, 'show']);
            Route::get('auditoria/ciot/{id}', [AuditoriaController::class, 'consultarCiot']);
            
            // ZT-DEFENSE: Rota do Proxy Local adicionada para leitura segura de KYC e PODs
            Route::get('perfil/documento', [EmbarcadorPerfilController::class, 'exibirDocumento']);
            Route::get('cargas/documento/pod', [EmbarcadorCargaController::class, 'exibirDocumentoPod']);
        });

        // =========================================================
        // DOMÍNIO: MOTORISTA
        // =========================================================
        Route::middleware('ability:motorista')->prefix('motorista')->group(function () {
            Route::get('perfil', [MotoristaPerfilController::class, 'show']);
            Route::post('perfil/documentos', [MotoristaPerfilController::class, 'uploadDocumentos']); 
            Route::get('perfil/documento/{tipo}', [MotoristaPerfilController::class, 'exibirDocumento']); 
            
            Route::get('carteira/extrato', [CarteiraController::class, 'extrato']);
            
            Route::get('cargas/disponiveis', [MotoristaCargaController::class, 'disponiveis']);
            Route::get('cargas/minhas', [MotoristaCargaController::class, 'minhasCargas']);
            Route::post('cargas/{id}/aceitar', [MotoristaCargaController::class, 'aceitar'])->middleware('throttle:10,1');
            Route::delete('cargas/{id}/aceitar', [MotoristaCargaController::class, 'cancelarAceite']);
            Route::post('cargas/{id}/iniciar-viagem', [MotoristaCargaController::class, 'iniciarViagem']);
            
            // ZT-DEFENSE: Mapeamento Atômico do Envio Seguro de POD (Substitui as Rotas Zumbis)
            Route::post('cargas/{id}/finalizar', [MotoristaCargaController::class, 'finalizarEntrega']);

            Route::get('cargas/{carga}/chat', [MotoristaCargaController::class, 'getChat']);
            Route::post('cargas/{carga}/chat', [MotoristaCargaController::class, 'storeChat'])->middleware('throttle:20,1');
        });

        // =========================================================
        // DOMÍNIO: ADMIN
        // =========================================================
        Route::middleware('ability:admin')->prefix('admin')->group(function () {

            // Dashboards e Relatórios
            Route::get('/dashboard', [AdminController::class, 'dashboardMetrics']);
            Route::get('/dashboard-stats', [AdminController::class, 'getDashboardStats']);

            // 🛑 PREVENÇÃO DE ROUTE SHADOWING: Rotas de Fretes organizadas
            // As rotas estáticas absolutas DEVEM preceder as dinâmicas ({id})
            Route::get('/fretes', [AdminController::class, 'listarFretes']);
            Route::get('/fretes/concluidos', [AdminController::class, 'fretesConcluidos']);
            Route::get('/operacoes/fretes', [AdminController::class, 'listarMuralFretes']);
            
            Route::get('/fretes/{id}', [AdminController::class, 'detalhesFrete']);
            Route::get('/fretes/{id}/auditoria', [AdminController::class, 'auditoriaCarga']);
            
            // ZT-DEFENSE: Proxy seguro de documentos (LFI Aniquilado)
            Route::get('/auditoria/documento', [AdminController::class, 'exibirDocumentoAuditoria']);
            Route::get('/kyc/documento', [AdminController::class, 'exibirDocumentoKyc']);

            // Disputas
            Route::get('/disputas', [AdminController::class, 'listarDisputas']);
            Route::get('/operacoes/disputas', [AdminController::class, 'listarDisputas']);
            Route::post('/disputas/{id}/resolver', [AdminController::class, 'resolverDisputa']);
            Route::post('/operacoes/disputas/{carga}/resolver', [AdminController::class, 'resolverDisputa']);
            
            // Faturamento
            Route::get('/faturamento/radar', [AdminFaturamentoController::class, 'radar']);
            Route::get('/faturamento/ciclos', [AdminFaturamentoController::class, 'listarCiclos']);
            Route::post('/faturamento/gerar', [AdminFaturamentoController::class, 'gerarFaturasManuais']);
            Route::get('/faturamento/extrato-taxas', [AdminFaturamentoController::class, 'extratoTaxasPlataforma']);
            Route::get('/faturamento/taxas-agregadas', [AdminFaturamentoController::class, 'taxasAgregadas']);
            Route::post('/faturamento/congelar/{embarcadorId}', [AdminFaturamentoController::class, 'congelar']);
            Route::get('/financeiro/extrato', [AdminController::class, 'extratoTaxas']);
            
            // Variáveis Globais e Staff
            Route::get('/config/variaveis', [AdminController::class, 'listarVariaveis']);
            Route::put('/config/variaveis', [AdminController::class, 'atualizarVariaveis']);
            Route::put('/variaveis', [AdminController::class, 'atualizarVariaveis']);
            
            Route::get('/config/staff', [AdminController::class, 'listarStaff']);
            Route::get('/staff', [AdminController::class, 'listarStaff']);
            Route::post('/config/staff', [AdminController::class, 'criarStaff']);
            Route::post('/staff', [AdminController::class, 'criarStaff']); 
            Route::put('/config/staff/{usuario}', [AdminController::class, 'atualizarStaff']);
            Route::put('/staff/{usuario}', [AdminController::class, 'atualizarStaff']); 
            
            // Usuários e CRM
            Route::get('/usuarios', [AdminController::class, 'listarTodosUsuarios']);
            Route::get('/usuarios-pendentes', [AdminController::class, 'usuariosPendentes']);
            Route::post('/usuarios/{usuario}/analise', [AdminController::class, 'analisarUsuario']);
            Route::post('/usuarios/{usuario}/status', [AdminController::class, 'alterarStatus']);
            
            Route::get('/embarcadores', [AdminController::class, 'listarEmbarcadores']);
            Route::get('/crm/embarcadores', [AdminController::class, 'listarEmbarcadores']);
            Route::get('/embarcadores/{id}', [AdminController::class, 'detalhesEmbarcador']);
            
            Route::get('/motoristas', [AdminController::class, 'listarMotoristas']);
            Route::get('/crm/motoristas', [AdminController::class, 'listarMotoristas']);
            Route::get('/motoristas/{id}', [AdminController::class, 'detalhesMotorista']);
            Route::post('/motoristas/{id}/kyc', [AdminController::class, 'avaliarKycMotorista']);
            
            Route::put('/crm/embarcadores/{embarcador}/contrato', [AdminController::class, 'atualizarContratoEmbarcador']);
            Route::put('/config/crm/embarcadores/{embarcador}/contrato', [AdminController::class, 'atualizarContratoEmbarcador']);
            
            // 🔒 ZERO TRUST: Parceiros e Integrações (API Gateway / Admin CRM)
            Route::get('/crm/parceiros', [ParceiroController::class, 'index']);
            Route::post('/crm/parceiros', [ParceiroController::class, 'store'])->middleware('throttle:30,1');
            Route::put('/crm/parceiros/{parceiro}', [ParceiroController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/crm/parceiros/{parceiro}', [ParceiroController::class, 'destroy'])->middleware('throttle:30,1');
            
            Route::get('/parceiros-api', [AdminController::class, 'listarParceirosApi']);
            Route::post('/parceiros-api', [AdminController::class, 'gerarTokenParceiro']);
            Route::post('/parceiros-api/{tokenId}/revogar', [AdminController::class, 'revogarTokenParceiro']);
            
            // Mesa de Operações (Tickets)
            Route::get('/suporte/tickets', [TicketController::class, 'index']);
            Route::get('/suporte/tickets/{ticket}', [TicketController::class, 'show']);
            Route::post('/suporte/tickets/{ticket}/assumir', [TicketController::class, 'assumirTicket']);
            Route::post('/suporte/tickets/{ticket}/responder', [TicketController::class, 'reply']);
            Route::post('/suporte/tickets/{ticket}/fechar', [TicketController::class, 'fecharTicket']);
        });
    });

    Route::middleware(['auth:sanctum', 'ability:gr-partner'])->prefix('partners/gr')->group(function () {
        Route::post('/analise/callback', [\App\Http\Controllers\Api\V1\Partners\GrIntegrationController::class, 'registrarAnalise']);
    });

    Route::put('/upload-mock', function() { return response()->json(['ok' => true]); });
});

Route::prefix('v1/localidades')->group(function () {
    Route::get('/estados', [\App\Http\Controllers\Api\V1\LocalidadeController::class, 'estados']);
    Route::get('/estados/{uf}/municipios', [\App\Http\Controllers\Api\V1\LocalidadeController::class, 'municipios']);
});

// =========================================================
// WEBHOOKS (Fora da blindagem Sanctum, protegidos por Token Interno)
// =========================================================
Route::post('/v1/webhooks/pef', [PefWebhookController::class, 'handleCallback'])->name('webhook.pef');
Route::post('/v1/webhooks/transat', [TransatWebhookController::class, 'handleCallback'])->name('webhook.transat');