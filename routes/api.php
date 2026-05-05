<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

// ==========================================
// Mapeamento Estrito: Arquitetura Modular V1
// Usando "as" para evitar colisão de nomes de Controllers
// ==========================================
use App\Http\Controllers\Api\V1\Auth\AuthController;

// Modulo Embarcador
use App\Http\Controllers\Api\V1\Embarcador\CargaController as EmbarcadorCargaController;
use App\Http\Controllers\Api\V1\Embarcador\FaturaController;
use App\Http\Controllers\Api\V1\Embarcador\PerfilController as EmbarcadorPerfilController;
use App\Http\Controllers\Api\V1\Embarcador\AuditoriaController; // INJETADO: Mesa de Auditoria

// Modulo Motorista
use App\Http\Controllers\Api\V1\Motorista\CargaController as MotoristaCargaController;
use App\Http\Controllers\Api\V1\Motorista\PerfilController as MotoristaPerfilController;
use App\Http\Controllers\Api\V1\Motorista\PodController; // INJETADO: Direct-to-S3 Upload

// Modulo Admin & Support
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Support\TicketController;
use App\Http\Controllers\Api\V1\Support\FaqController;

// Webhooks
use App\Http\Controllers\Api\V1\Webhooks\PefWebhookController; // INJETADO: Retorno Financeiro

// Models e Jobs para Testes
use App\Models\Carga;
use App\Models\User;
use App\Jobs\ProcessarAceiteCarga;
use App\Jobs\ProcessarPublicacaoCarga;

/*
|--------------------------------------------------------------------------
| API Routes V1 (123fretei TMS)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ==========================================
    // ROTAS PÚBLICAS E WEBHOOKS
    // ==========================================
    
    // --- Autenticação (Rate Limiting Paranoico) ---
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:3,1');
    Route::post('/register/embarcador', [AuthController::class, 'registerEmbarcador'])->middleware('throttle:3,1');
    Route::post('/register/motorista', [AuthController::class, 'registerMotorista'])->middleware('throttle:3,1');

    // --- Webhooks de Gateways ---
    // A validação de segurança (X-PEF-Signature) é feita dentro do próprio Controller/Middleware.
    Route::post('/webhooks/pef', [PefWebhookController::class, 'handle'])->middleware('throttle:120,1');

    // ==========================================
    // ROTAS PROTEGIDAS (Sanctum Nativo + Anti-DDoS Básico)
    // ==========================================
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        
        // --- Autenticação e Mural ---
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // --- ÁREA DO CLIENTE: SAC (Comum para Embarcador e Motorista) ---
        Route::prefix('suporte')->group(function () {
            Route::get('/faqs', [FaqController::class, 'index']);
            Route::get('/tickets', [TicketController::class, 'meusTickets']);
            Route::post('/tickets', [TicketController::class, 'abrirTicket']);
            Route::get('/tickets/{ticket}', [TicketController::class, 'exibirTicket']);
            Route::post('/tickets/{ticket}/responder', [TicketController::class, 'responderTicket']);
        });

        // --- ÁREA EXCLUSIVA: EMBARCADOR ---
        Route::middleware('role:embarcador')->prefix('embarcador')->group(function () {
            // Gestão de Cargas
            Route::post('/cargas', [EmbarcadorCargaController::class, 'store']); 
            Route::put('/cargas/{carga}', [EmbarcadorCargaController::class, 'update']); 
            Route::delete('/cargas/{carga}', [EmbarcadorCargaController::class, 'destroy']); 
            Route::get('/cargas', [EmbarcadorCargaController::class, 'index']); 
            Route::get('/cargas/{carga}', [EmbarcadorCargaController::class, 'show']); 
            
            // Auditoria e Liberação Financeira (Injetado)
            Route::post('/cargas/{carga}/aprovar', [AuditoriaController::class, 'aprovarPagamento']);
            Route::post('/cargas/{carga}/disputa', [AuditoriaController::class, 'abrirDisputa']);

            // Perfil e Financeiro
            Route::get('/perfil', [EmbarcadorPerfilController::class, 'show']);
            Route::put('/perfil', [EmbarcadorPerfilController::class, 'update']);
            
            Route::get('/faturas', [FaturaController::class, 'index']);
            Route::get('/faturas/{id}', [FaturaController::class, 'show']);
        });

        // --- ÁREA EXCLUSIVA: MOTORISTA ---
        Route::middleware('role:motorista')->prefix('motorista')->group(function () {
            // Feed de cargas disponíveis e as cargas do motorista
            Route::get('/cargas/disponiveis', [MotoristaCargaController::class, 'disponiveis']); 
            Route::get('/cargas/minhas', [MotoristaCargaController::class, 'minhasCargas']); 
            
            // Ações transacionais (Com Rate Limit agressivo para evitar flood de clique)
            Route::post('/cargas/{id}/aceitar', [MotoristaCargaController::class, 'aceitar'])->middleware('throttle:10,1'); 
            Route::post('/cargas/{carga}/cancelar-aceite', [MotoristaCargaController::class, 'cancelarAceite']); 
            Route::post('/cargas/{carga}/iniciar-viagem', [MotoristaCargaController::class, 'iniciarViagem']); 
            Route::post('/cargas/{carga}/finalizar-entrega', [MotoristaCargaController::class, 'finalizarEntrega']); 

            // Proof of Delivery - Direct-to-S3 Uploads (Injetado)
            Route::post('/cargas/{carga}/pod/url', [PodController::class, 'gerarUrlUpload']);
            Route::post('/cargas/{carga}/pod/confirmar', [PodController::class, 'confirmarEntrega']);
            
            Route::get('/perfil', [MotoristaPerfilController::class, 'show']);
            Route::post('/perfil/documentos', [MotoristaPerfilController::class, 'uploadDocumentos']);
        });

        // --- ÁREA EXCLUSIVA: ADMINISTRAÇÃO (BACKOFFICE / STAFF) ---
        Route::middleware('role:admin,manager,compliance,suporte_n1')->prefix('admin')->group(function () {
            
            Route::get('/dashboard-stats', [AdminController::class, 'getDashboardStats']);
            Route::get('/usuarios-pendentes', [AdminController::class, 'usuariosPendentes']);
            Route::post('/usuarios/{usuario}/analise', [AdminController::class, 'analisarUsuario']);
            Route::get('/usuarios', [AdminController::class, 'listarTodosUsuarios']);
            Route::post('/usuarios/{usuario}/status', [AdminController::class, 'alterarStatus']);
            Route::get('/fretes', [AdminController::class, 'relatorioFretes']);

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

            Route::get('/financeiro/extrato', [AdminController::class, 'extratoTaxas']);
            Route::get('/financeiro/faturamento', [AdminController::class, 'relatorioFaturamento']);

            Route::get('/config/staff', [AdminController::class, 'listarStaff']);
            Route::post('/config/staff', [AdminController::class, 'criarStaff']); 
            Route::put('/config/staff/{usuario}', [AdminController::class, 'atualizarStaff']); 
            Route::get('/config/variaveis', [AdminController::class, 'listarVariaveis']);
            Route::put('/config/variaveis', [AdminController::class, 'atualizarVariaveis']);
        });
    });

    // ==========================================
    // TESTES DE STRESS (Bloqueado em Produção)
    // ==========================================
    if (App::environment('local', 'testing', 'staging')) {
        Route::post('/stress-test/aceitar', function (Request $request) {
            $carga = Carga::first(); 
            $user = User::where('email', 'mot@stress.com')->first(); 
            if (!$carga || !$user) return response()->json(['error' => 'Dados insuficientes para teste'], 400);

            ProcessarAceiteCarga::dispatch($carga, $user, $request->ip(), 'Stress-Test-Bot');
            return response()->json(['message' => 'Accepted'], 202);
        });

        Route::post('/stress-test/publicar', function (Request $request) {
            $user = User::where('email', 'emb@stress.com')->first();
            if (!$user || !$user->embarcador) return response()->json(['error' => 'Embarcador não encontrado.'], 400);

            $cargaData = [
                'produto' => 'Carga de Stress', 'especie' => 'Caixas', 'peso_kg' => random_int(1000, 5000),
                'tipo_veiculo' => 'Truck', 'tipo_carroceria' => 'Baú', 'cidade_origem' => 'SP', 'uf_origem' => 'SP',
                'cidade_destino' => 'RJ', 'uf_destino' => 'RJ', 'valor_frete' => 5000, 'data_coleta' => now()->format('Y-m-d')
            ];

            ProcessarPublicacaoCarga::dispatch($cargaData, $user->embarcador->id, $request->ip(), 'Node-Stress-Test')->onQueue('default');
            return response()->json(['message' => 'Lote enviado para a fila'], 202);
        });
    }
});