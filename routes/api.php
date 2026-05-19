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
use App\Http\Controllers\Api\V1\Motorista\CarteiraController;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\ParceiroController;
use App\Http\Controllers\Api\V1\Admin\FaturamentoController as AdminFaturamentoController;
use App\Http\Controllers\Api\V1\Support\TicketController;
use App\Http\Controllers\Api\V1\Support\FaqController;
use App\Http\Controllers\Api\V1\Webhooks\PefWebhookController; 

Route::prefix('v1')->group(function () {

    // =========================================================
    // AUTENTICAÇÃO E IDENTIDADE
    // =========================================================
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/register/embarcador', [AuthController::class, 'registerEmbarcador']);
    Route::post('/register/motorista', [AuthController::class, 'registerMotorista']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // =========================================================
        // DOMÍNIO: SUPORTE, FAQ & HUB (Transversal)
        // =========================================================
        Route::get('/suporte/faqs', [FaqController::class, 'index']); // Corrigido para plural
        Route::get('/suporte/tickets', [TicketController::class, 'index']);
        Route::post('/suporte/tickets', [TicketController::class, 'store'])->middleware('throttle:5,1');
        Route::get('/suporte/tickets/{ticket}', [TicketController::class, 'show']);
        Route::post('/suporte/tickets/{ticket}/mensagens', [TicketController::class, 'reply'])->middleware('throttle:15,1');
        
        Route::get('/hub/parceiros', [ParceiroController::class, 'index']); // Rota da Loja Liberada

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
        });

        // =========================================================
        // DOMÍNIO: MOTORISTA
        // =========================================================
        Route::middleware('ability:motorista')->prefix('motorista')->group(function () {
            
            // Perfil & KYC (Alinhado com a sua base)
            Route::get('perfil', [MotoristaPerfilController::class, 'show']);
            Route::post('perfil/documentos', [MotoristaPerfilController::class, 'uploadDocumentos']); 
            Route::get('perfil/documento/{tipo}', [MotoristaPerfilController::class, 'exibirDocumento']); 
            
            Route::get('carteira/extrato', [CarteiraController::class, 'extrato']);
            
            // Gestão de Bidding e Execução
            Route::get('cargas/disponiveis', [MotoristaCargaController::class, 'disponiveis']);
            Route::get('cargas/minhas', [MotoristaCargaController::class, 'minhasCargas']);
            Route::post('cargas/{id}/aceitar', [MotoristaCargaController::class, 'aceitar'])->middleware('throttle:10,1');
            Route::delete('cargas/{id}/aceitar', [MotoristaCargaController::class, 'cancelarAceite']);
            Route::post('cargas/{id}/iniciar-viagem', [MotoristaCargaController::class, 'iniciarViagem']);
            
            // Fluxo de POD com o seu PodController Base (S3 Mock) preservado
            Route::post('cargas/{carga}/pod/url', [PodController::class, 'gerarUrlUpload']);
            Route::post('cargas/{carga}/pod/confirmar', [PodController::class, 'confirmarEntrega']);

            Route::get('cargas/{carga}/chat', [MotoristaCargaController::class, 'getChat']);
            Route::post('cargas/{carga}/chat', [MotoristaCargaController::class, 'storeChat'])->middleware('throttle:20,1');
        });

        // =========================================================
        // DOMÍNIO: ADMIN
        // =========================================================
        Route::middleware('ability:admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboardMetrics']);
            Route::get('/embarcadores', [AdminController::class, 'listarEmbarcadores']);
            Route::get('/embarcadores/{id}', [AdminController::class, 'detalhesEmbarcador']);
            Route::get('/motoristas', [AdminController::class, 'listarMotoristas']);
            Route::get('/motoristas/{id}', [AdminController::class, 'detalhesMotorista']);
            Route::post('/motoristas/{id}/kyc', [AdminController::class, 'avaliarKycMotorista']);
            Route::get('/fretes', [AdminController::class, 'listarFretes']);
            Route::get('/fretes/{id}', [AdminController::class, 'detalhesFrete']);
            Route::get('/disputas', [AdminController::class, 'listarDisputas']);
            Route::post('/disputas/{id}/resolver', [AdminController::class, 'resolverDisputa']);
            
            Route::get('/faturamento/radar', [AdminFaturamentoController::class, 'radar']);
            Route::post('/faturamento/congelar/{embarcadorId}', [AdminFaturamentoController::class, 'congelar']);
            Route::get('/faturamento/ciclos', [AdminFaturamentoController::class, 'listarCiclos']);
            Route::post('/faturamento/gerar', [AdminFaturamentoController::class, 'gerarFaturasManuais']);
            Route::get('/faturamento/extrato-taxas', [AdminFaturamentoController::class, 'extratoTaxasPlataforma']);
            Route::get('/faturamento/taxas-agregadas', [AdminFaturamentoController::class, 'taxasAgregadas']);
            
            Route::get('/staff', [AdminController::class, 'listarStaff']);
            Route::post('/staff', [AdminController::class, 'criarStaff']); 
            Route::put('/staff/{usuario}', [AdminController::class, 'atualizarStaff']); 
            Route::put('/variaveis', [AdminController::class, 'atualizarVariaveis']);
            Route::put('/crm/embarcadores/{embarcador}/contrato', [AdminController::class, 'atualizarContratoEmbarcador']);
            
            Route::post('/crm/parceiros', [ParceiroController::class, 'store']);
            Route::put('/crm/parceiros/{parceiro}', [ParceiroController::class, 'update']);
            Route::delete('/crm/parceiros/{parceiro}', [ParceiroController::class, 'destroy']);
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

Route::post('/v1/webhooks/pef', [PefWebhookController::class, 'handleCallback'])->name('webhook.pef');