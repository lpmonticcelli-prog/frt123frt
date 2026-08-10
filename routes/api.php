<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Api\V1\Auth\AuthController;

// Embarcador
use App\Http\Controllers\Api\V1\Embarcador\CargaController as EmbarcadorCargaController;
use App\Http\Controllers\Api\V1\Embarcador\FaturaController;
use App\Http\Controllers\Api\V1\Embarcador\PerfilController as EmbarcadorPerfilController;
use App\Http\Controllers\Api\V1\Embarcador\DocumentoFiscalController;
// use App\Http\Controllers\Api\V1\Embarcador\CheckoutController;
use App\Http\Controllers\Api\V1\Embarcador\LocalOperacionalController;

// Motorista
use App\Http\Controllers\Api\V1\Motorista\CargaController as MotoristaCargaController;
use App\Http\Controllers\Api\V1\Motorista\PerfilController as MotoristaPerfilController;
use App\Http\Controllers\Api\V1\Motorista\CarteiraController;
use App\Http\Controllers\Api\V1\Motorista\GrController;

// Admin
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\ParceiroController;
use App\Http\Controllers\Api\V1\Admin\FaturamentoController as AdminFaturamentoController;

// Support & Hub
use App\Http\Controllers\Api\V1\Support\TicketController;
use App\Http\Controllers\Api\V1\Support\FaqController;
use App\Http\Controllers\Api\V1\LocalidadeController;

// Partners & Webhooks
use App\Http\Controllers\Api\V1\Partners\GrIntegrationController;
use App\Http\Controllers\Api\V1\Webhooks\PefWebhookController;
use App\Http\Controllers\Api\V1\Webhooks\GatewayWebhookController;

Route::prefix('v1')->group(function () {

    // =========================================================
    // PUBLIC ENDPOINTS & AUTH (Zero Trust Entrypoints)
    // =========================================================
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login')->middleware('throttle:5,1');
        Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:3,1');
        Route::post('/reset-password', 'resetPassword')->middleware('throttle:5,1');
        Route::post('/register/embarcador', 'registerEmbarcador')->middleware(['throttle:3,1', 'idempotency']);
        Route::post('/register/motorista', 'registerMotorista')->middleware(['throttle:3,1', 'idempotency']);
    });

    // =========================================================
    // LOCATIONS (Public / Read-only / Proxy)
    // =========================================================
    Route::prefix('localidades')->controller(LocalidadeController::class)->group(function () {
        Route::get('/estados', 'estados')->middleware('cache.headers:public;max_age=86400');
        Route::get('/estados/{uf}/municipios', 'municipios')->middleware('cache.headers:public;max_age=86400');
        Route::get('/cep/{cep}', 'buscarCep')->middleware('throttle:30,1');
    });

    // =========================================================
    // AUTHENTICATED ENDPOINTS (Stateless PAT Tokens)
    // =========================================================
    Route::middleware('auth:sanctum')->group(function () {
        
        // GLOBAL AUTH (Corrigido para bater com o Frontend)
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // HUB / BENEFITS MARKETPLACE
        Route::prefix('hub/parceiros')->controller(ParceiroController::class)->group(function () {
            Route::get('/', 'listarPorPublico')->middleware('throttle:120,1');
            Route::post('/{parceiro}/clique', 'registrarClique')->middleware('throttle:10,1');
            Route::post('/{parceiro}/conversao', 'registrarConversao')->middleware('throttle:10,1');
        });

        // SUPPORT
        Route::prefix('suporte')->group(function () {
            Route::get('/faqs', [FaqController::class, 'index']);
            
            Route::controller(TicketController::class)->prefix('tickets')->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store')->middleware('throttle:5,1');
                Route::get('/{ticket}', 'show');
                Route::post('/{ticket}/mensagens', 'reply')->middleware('throttle:15,1');
            });
        });

        // =========================================================
        // BOUNDED CONTEXT: EMBARCADOR
        // =========================================================
        Route::middleware('ability:embarcador')->prefix('embarcador')->group(function () {
            
            // Identity
            Route::controller(EmbarcadorPerfilController::class)->prefix('perfil')->group(function () {
                Route::get('/', 'show');
                Route::put('/', 'update');
                Route::get('/documento', 'exibirDocumento');
            });
            
            // Operations
            Route::apiResource('locais', LocalOperacionalController::class)->only(['index', 'store', 'destroy']);
            
            Route::controller(EmbarcadorCargaController::class)->prefix('cargas')->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('/{carga}', 'show');
                Route::put('/{carga}', 'update');
                Route::delete('/{carga}', 'destroy');
                
                Route::post('/{carga}/candidaturas/aprovar', 'aprovarCandidato')->middleware(['throttle:10,1', 'idempotency']);
                Route::post('/{carga}/avaliar', 'avaliarEFinalizarEntrega')->middleware('throttle:5,1');
                Route::post('/{carga}/disputa', 'abrirDisputa')->middleware(['throttle:5,1', 'idempotency']);
                
                Route::get('/documento/pod', 'exibirDocumentoPod'); 
                Route::get('/{carga}/chat', 'getChat');
                Route::post('/{carga}/chat', 'storeChat')->middleware('throttle:20,1');
            });

            // Documentos e POD
            Route::post('documentos/xml/parse', [DocumentoFiscalController::class, 'parse'])->middleware('throttle:10,1');

            // Financial
            Route::get('faturas', [FaturaController::class, 'index']);
            Route::get('faturas/{fatura}', [FaturaController::class, 'show']);
        });

        // =========================================================
        // BOUNDED CONTEXT: MOTORISTA
        // =========================================================
        Route::middleware('ability:motorista')->prefix('motorista')->group(function () {
            
            // Identity
            Route::controller(MotoristaPerfilController::class)->prefix('perfil')->group(function () {
                Route::get('/', 'show');
                Route::post('/', 'update'); 
                Route::get('/documento/{tipo}', 'exibirDocumento'); 
            });
            
            // Feature Toggle GR
            if (config('services.gr.enabled', false)) {
                Route::post('perfil/gr/solicitar', [GrController::class, 'solicitarAnalise'])->middleware(['throttle:3,1', 'idempotency']);
            }
            
            // Financial
            Route::get('carteira/extrato', [CarteiraController::class, 'extrato']);
            
            // Operations 
            Route::controller(MotoristaCargaController::class)->prefix('cargas')->group(function () {
                Route::get('/disponiveis', 'disponiveis');
                Route::get('/minhas', 'minhasCargas');
                
                Route::post('/{carga}/aceitar', 'aceitar')->middleware(['throttle:10,1', 'idempotency']);
                Route::delete('/{carga}/aceitar', 'cancelarAceite');
                Route::post('/{carga}/iniciar-viagem', 'iniciarViagem');
                Route::post('/{carga}/finalizar', 'finalizarEntrega')->middleware(['throttle:5,1', 'idempotency']);
                
                Route::get('/{carga}/chat', 'getChat');
                Route::post('/{carga}/chat', 'storeChat')->middleware('throttle:20,1');
            });
        });

        // =========================================================
        // BOUNDED CONTEXT: ADMIN
        // =========================================================
        Route::middleware('ability:admin')->prefix('admin')->group(function () {
            
            Route::controller(AdminController::class)->group(function () {
                Route::get('/dashboard', 'dashboardMetrics');
                Route::get('/dashboard-stats', 'getDashboardStats');

                // Logistics
                Route::get('/fretes', 'listarFretes');
                Route::get('/fretes/concluidos', 'fretesConcluidos');
                Route::get('/fretes/{carga}', 'detalhesFrete');
                Route::get('/fretes/{carga}/auditoria', 'auditoriaCarga');
                Route::get('/auditoria/documento', 'exibirDocumentoAuditoria');
                
                // Disputes
                Route::get('/disputas', 'listarDisputas');
                Route::post('/disputas/{disputa}/resolver', 'resolverDisputa');
                
                // Extrato Financeiro Global
                Route::get('/financeiro/extrato', 'extratoTaxas');
                
                // Config & Staff 
                Route::get('/config/variaveis', 'listarVariaveis');
                Route::put('/config/variaveis', 'atualizarVariaveis');
                Route::get('/staff', 'listarStaff');
                Route::post('/staff', 'criarStaff'); 
                Route::put('/staff/{usuario}', 'atualizarStaff');
                
                // Identity Management
                Route::get('/usuarios', 'listarTodosUsuarios');
                Route::get('/usuarios-pendentes', 'usuariosPendentes');
                Route::post('/usuarios/{usuario}/analise', 'analisarUsuario');
                Route::post('/usuarios/{usuario}/status', 'alterarStatus');
                Route::get('/kyc/documento', 'exibirDocumentoKyc');
                
                // CRM 
                Route::get('/embarcadores', 'listarEmbarcadores');
                Route::get('/embarcadores/{embarcador}', 'detalhesEmbarcador');
                Route::put('/embarcadores/{embarcador}/contrato', 'atualizarContratoEmbarcador');
                
                Route::get('/motoristas', 'listarMotoristas');
                Route::get('/motoristas/{motorista}', 'detalhesMotorista');
                Route::post('/motoristas/{motorista}/kyc', 'avaliarKycMotorista');
                
                // API B2B
                Route::get('/parceiros-api', 'listarParceirosApi');
                Route::post('/parceiros-api', 'gerarTokenParceiro');
                Route::post('/parceiros-api/{tokenId}/revogar', 'revogarTokenParceiro');
            });
            
            // Parceiros de Negócios (Hub)
            Route::apiResource('crm/parceiros', ParceiroController::class)->only(['index', 'store', 'update', 'destroy']);
            
            // Faturamento SaaS
            Route::controller(AdminFaturamentoController::class)->prefix('faturamento')->group(function () {
                Route::get('/radar', 'radar');
                Route::get('/ciclos', 'listarCiclos');
                Route::post('/gerar', 'gerarFaturasManuais')->middleware('idempotency');
                Route::get('/extrato-taxas', 'extratoTaxasPlataforma');
                Route::get('/taxas-agregadas', 'taxasAgregadas');
                Route::post('/congelar/{embarcador}', 'congelar');
            });
            
            // Support Admin
            Route::controller(TicketController::class)->prefix('suporte/tickets')->group(function () {
                Route::get('/', 'index');
                Route::get('/{ticket}', 'show');
                Route::post('/{ticket}/assumir', 'assumirTicket');
                Route::post('/{ticket}/responder', 'reply');
                Route::post('/{ticket}/fechar', 'fecharTicket');
            });
        });
    });

    // =========================================================
    // PARCEIROS B2B (GR API) - Isolated Context
    // =========================================================
    if (config('services.gr.enabled', false)) {
        Route::middleware(['auth:sanctum', 'ability:gr-partner'])->prefix('partners/gr')->group(function () {
            Route::post('/analise/callback', [GrIntegrationController::class, 'registrarAnalise'])->middleware('throttle:60,1');
        });
    }
});

// =========================================================
// EXTERNAL WEBHOOKS (Offloading Zero Trust Pipeline)
// =========================================================
Route::prefix('v1/webhooks')->middleware(['throttle:100,1'])->group(function () {
    Route::post('/pef', [PefWebhookController::class, 'handleCallback'])
        ->middleware('b2b.hmac:pef')
        ->name('webhook.pef');
        
    Route::post('/gateway', [GatewayWebhookController::class, 'handleCallback'])
        ->middleware('b2b.hmac:gateway')
        ->name('webhook.gateway');
});