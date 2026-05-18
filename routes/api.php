<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Embarcador\CargaController as EmbarcadorCargaController;
use App\Http\Controllers\Api\V1\Embarcador\FaturaController;
use App\Http\Controllers\Api\V1\Embarcador\PerfilController as EmbarcadorPerfilController;
use App\Http\Controllers\Api\V1\Embarcador\AuditoriaController; 
use App\Http\Controllers\Api\V1\Motorista\CargaController as MotoristaCargaController;
use App\Http\Controllers\Api\V1\Motorista\PerfilController as MotoristaPerfilController;
use App\Http\Controllers\Api\V1\Motorista\PodController; 
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\ParceiroController;
use App\Http\Controllers\Api\V1\Admin\FaturamentoController as AdminFaturamentoController;
use App\Http\Controllers\Api\V1\Support\TicketController;
use App\Http\Controllers\Api\V1\Support\FaqController;
use App\Http\Controllers\Api\V1\Webhooks\PefWebhookController; 

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:3,1');
    Route::post('/register/embarcador', [AuthController::class, 'registerEmbarcador'])->middleware('throttle:3,1');
    Route::post('/register/motorista', [AuthController::class, 'registerMotorista'])->middleware('throttle:3,1');

    Route::post('/webhooks/pef', [PefWebhookController::class, 'handle'])->middleware('throttle:120,1');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // =========================================================
        // HUB: Rotas de consumo (Motoristas e Embarcadores)
        // =========================================================
        Route::get('/hub/parceiros', [ParceiroController::class, 'listarPorPublico']);
        Route::post('/hub/parceiros/{parceiro}/clique', [ParceiroController::class, 'registrarClique']);

        Route::prefix('suporte')->group(function () {
            Route::get('/faqs', [FaqController::class, 'index']);
            Route::get('/tickets', [TicketController::class, 'meusTickets']);
            Route::post('/tickets', [TicketController::class, 'abrirTicket']);
            Route::get('/tickets/{ticket}', [TicketController::class, 'exibirTicket']);
            Route::post('/tickets/{ticket}/responder', [TicketController::class, 'responderTicket']);
        });

        Route::middleware('role:embarcador')->prefix('embarcador')->group(function () {
            Route::post('/cargas', [EmbarcadorCargaController::class, 'store']); 
            Route::put('/cargas/{carga}', [EmbarcadorCargaController::class, 'update']); 
            Route::delete('/cargas/{carga}', [EmbarcadorCargaController::class, 'destroy']); 
            Route::get('/cargas', [EmbarcadorCargaController::class, 'index']); 
            Route::get('/cargas/{carga}', [EmbarcadorCargaController::class, 'show']); 
            Route::post('/cargas/{carga}/aprovar', [EmbarcadorCargaController::class, 'aprovarEntrega']);
            Route::post('/cargas/{carga}/disputa', [AuditoriaController::class, 'abrirDisputa']);

            Route::get('/perfil', [EmbarcadorPerfilController::class, 'show']);
            Route::put('/perfil', [EmbarcadorPerfilController::class, 'update']);
            
            // Faturamento B2B do Embarcador
            Route::get('/faturas', [FaturaController::class, 'index']);
            Route::get('/faturas/{id}', [FaturaController::class, 'show']);
        });

        Route::middleware('role:motorista')->prefix('motorista')->group(function () {
            Route::get('/cargas/disponiveis', [MotoristaCargaController::class, 'disponiveis']); 
            Route::get('/cargas/minhas', [MotoristaCargaController::class, 'minhasCargas']); 
            Route::post('/cargas/{id}/aceitar', [MotoristaCargaController::class, 'aceitar'])->middleware('throttle:10,1'); 
            Route::post('/cargas/{id}/cancelar-aceite', [MotoristaCargaController::class, 'cancelarAceite']); 
            Route::post('/cargas/{id}/iniciar-viagem', [MotoristaCargaController::class, 'iniciarViagem']); 
            Route::post('/cargas/{carga}/pod/url', [PodController::class, 'gerarUrlUpload']);
            Route::post('/cargas/{carga}/pod/confirmar', [PodController::class, 'confirmarEntrega']);
            
            Route::get('/carteira/extrato', [\App\Http\Controllers\Api\V1\Motorista\CarteiraController::class, 'extrato']);
            
            Route::get('/perfil', [MotoristaPerfilController::class, 'show']);
            Route::post('/perfil/documentos', [MotoristaPerfilController::class, 'uploadDocumentos']);
            // ROTA DE PROXY SEGURO DE ARQUIVOS (MOTORISTA)
            Route::get('/perfil/documento/{tipo}', [MotoristaPerfilController::class, 'exibirDocumento']);
        });

        // =========================================================
        // BACKOFFICE: Rotas Gerais (Equipa Operacional)
        // =========================================================
        Route::middleware('role:admin,manager,compliance,suporte_n1')->prefix('admin')->group(function () {
            Route::get('/dashboard-stats', [AdminController::class, 'getDashboardStats']);
            Route::get('/usuarios-pendentes', [AdminController::class, 'usuariosPendentes']);
            Route::post('/usuarios/{usuario}/analise', [AdminController::class, 'analisarUsuario']);
            Route::get('/usuarios', [AdminController::class, 'listarTodosUsuarios']);
            Route::post('/usuarios/{usuario}/status', [AdminController::class, 'alterarStatus']);
            
            // =========================================================
            // Gestão de Fretes, Auditoria 360 e Proxies de Arquivos Seguros
            // =========================================================
            Route::get('/fretes', [AdminController::class, 'relatorioFretes']);
            Route::get('/fretes/concluidos', [AdminController::class, 'fretesConcluidos']);
            Route::get('/fretes/{id}/auditoria', [AdminController::class, 'auditoriaCarga']);
            Route::get('/auditoria/documento', [AdminController::class, 'exibirDocumentoAuditoria']); // Proxy Foto Canhoto
            Route::get('/kyc/documento', [AdminController::class, 'exibirDocumentoKyc']); // Proxy Documentos KYC
            
            Route::get('/operacoes/fretes', [AdminController::class, 'listarMuralFretes']);
            Route::get('/operacoes/disputas', [AdminController::class, 'listarDisputas']);
            Route::post('/operacoes/disputas/{carga}/resolver', [AdminController::class, 'resolverDisputa']);

            Route::prefix('suporte')->group(function () {
                Route::get('/tickets', [TicketController::class, 'listarFilaGlobal']);
                Route::get('/tickets/{ticket}', [TicketController::class, 'exibirTicket']);
                Route::post('/tickets/{ticket}/assumir', [TicketController::class, 'assumirTicket']);
                Route::post('/tickets/{ticket}/responder', [TicketController::class, 'responderTicket']);
                Route::post('/tickets/{ticket}/fechar', [TicketController::class, 'fecharTicket']);
            });

            Route::get('/crm/motoristas', [AdminController::class, 'listarMotoristas']);
            Route::get('/crm/embarcadores', [AdminController::class, 'listarEmbarcadores']);
            
            Route::get('/faturamento/radar', [AdminFaturamentoController::class, 'radar']);
            Route::post('/embarcadores/{id}/congelar', [AdminFaturamentoController::class, 'congelar'])->middleware('role:admin');
            
            Route::get('/financeiro/extrato', [AdminController::class, 'extratoTaxas']);
            Route::get('/financeiro/faturamento', [AdminController::class, 'relatorioFaturamento']);
            
            Route::get('/config/staff', [AdminController::class, 'listarStaff']);
            Route::get('/config/variaveis', [AdminController::class, 'listarVariaveis']);
            
            Route::get('/crm/parceiros', [ParceiroController::class, 'index']);

            // =========================================================
            // API GATEWAY (Gestão de Integrações M2M)
            // =========================================================
            Route::middleware('role:admin')->group(function () {
                Route::get('/parceiros-api', [AdminController::class, 'listarParceirosApi']);
                Route::post('/parceiros-api', [AdminController::class, 'gerarTokenParceiro']);
                Route::post('/parceiros-api/{id}/revogar', [AdminController::class, 'revogarTokenParceiro']);
            });
        });

        // =========================================================
        // BACKOFFICE: Rotas Críticas (Acesso EXCLUSIVO Admin Root)
        // =========================================================
        Route::middleware('role:admin')->prefix('admin/config')->group(function () {
            Route::post('/staff', [AdminController::class, 'criarStaff']); 
            Route::put('/staff/{usuario}', [AdminController::class, 'atualizarStaff']); 
            Route::put('/variaveis', [AdminController::class, 'atualizarVariaveis']);
            
            Route::put('/crm/embarcadores/{embarcador}/contrato', [AdminController::class, 'atualizarContratoEmbarcador']);
            
            Route::post('/crm/parceiros', [ParceiroController::class, 'store']);
            Route::put('/crm/parceiros/{parceiro}', [ParceiroController::class, 'update']);
            Route::delete('/crm/parceiros/{parceiro}', [ParceiroController::class, 'destroy']);
        });
    });

    // =========================================================
    // OPEN API B2B: PARCEIROS E GERENCIADORAS DE RISCO (GR / e3)
    // =========================================================
    Route::middleware(['auth:sanctum', 'ability:gr-partner'])->prefix('partners/gr')->group(function () {
        Route::post('/analise/callback', [\App\Http\Controllers\Api\V1\Partners\GrIntegrationController::class, 'registrarAnalise']);
    });

    Route::put('/upload-mock', function() { return response()->json(['ok' => true]); });
});

Route::prefix('v1/localidades')->group(function () {
    Route::get('/estados', [\App\Http\Controllers\Api\V1\LocalidadeController::class, 'estados']);
    Route::get('/estados/{uf}/municipios', [\App\Http\Controllers\Api\V1\LocalidadeController::class, 'municipios']);
});