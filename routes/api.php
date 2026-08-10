<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// 1. Auth & Public
use App\Http\Controllers\Api\V1\AnttController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\LocalidadeController;

// 2. Admin
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\FaturamentoController as AdminFaturamentoController;
use App\Http\Controllers\Api\V1\Admin\ParceiroController;

// 3. Embarcador
use App\Http\Controllers\Api\V1\Embarcador\AuditoriaController; 
use App\Http\Controllers\Api\V1\Embarcador\CargaController as EmbarcadorCargaController;
use App\Http\Controllers\Api\V1\Embarcador\CertificadoController;
use App\Http\Controllers\Api\V1\Embarcador\CheckoutController;
use App\Http\Controllers\Api\V1\Embarcador\DocumentoFiscalController;
use App\Http\Controllers\Api\V1\Embarcador\FaturaController;
use App\Http\Controllers\Api\V1\Embarcador\LocalController; 
use App\Http\Controllers\Api\V1\Embarcador\PerfilController as EmbarcadorPerfilController;

// 4. Motorista
use App\Http\Controllers\Api\V1\Motorista\CargaController as MotoristaCargaController;
use App\Http\Controllers\Api\V1\Motorista\CarteiraController;
use App\Http\Controllers\Api\V1\Motorista\GrController;
use App\Http\Controllers\Api\V1\Motorista\PerfilController as MotoristaPerfilController;

// 5. Suporte & Webhooks
use App\Http\Controllers\Api\V1\Partners\GrIntegrationController;
use App\Http\Controllers\Api\V1\Support\FaqController;
use App\Http\Controllers\Api\V1\Support\TicketController;
use App\Http\Controllers\Api\V1\Webhooks\GatewayWebhookController;
use App\Http\Controllers\Api\V1\Webhooks\PefWebhookController; 
use App\Http\Controllers\Api\V1\Webhooks\TransatWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // =========================================================
    // ROTAS PÚBLICAS
    // =========================================================
    
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login')->middleware('throttle:5,1');
        Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:3,1');
        Route::post('/reset-password', 'resetPassword')->middleware('throttle:5,1');
        Route::post('/register/embarcador', 'registerEmbarcador')->middleware('throttle:3,1');
        Route::post('/register/motorista', 'registerMotorista')->middleware('throttle:5,1');
    });

    Route::controller(LocalidadeController::class)->prefix('localidades')->group(function () {
        Route::get('/estados', 'estados');
        Route::get('/estados/{uf}/municipios', 'municipios');
        Route::get('/cep/{cep}', 'buscarCep');
    });

    Route::prefix('webhooks')->group(function () {
        Route::post('/pef', [PefWebhookController::class, 'handleCallback'])->name('webhook.pef');
        Route::post('/transat', [TransatWebhookController::class, 'handleCallback'])->name('webhook.transat');
        Route::post('/gateway-pagamento', [GatewayWebhookController::class, 'handleCallback'])->name('webhook.gateway');
    });

    // =========================================================
    // ROTAS PRIVADAS (Sanctum)
    // =========================================================
    Route::middleware('auth:sanctum')->group(function () {
        
        // Base / Utilitários
        Route::controller(AuthController::class)->group(function () {
            Route::post('/logout', 'logout');
            Route::get('/me', 'me');
        });

        Route::post('/antt/calcular', [AnttController::class, 'calcular']);
        Route::put('/upload-mock', function() { return response()->json(['ok' => true]); });
        Route::get('/suporte/faqs', [FaqController::class, 'index']);
        
        Route::controller(TicketController::class)->prefix('suporte/tickets')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store')->middleware('throttle:5,1');
            Route::prefix('{ticket}')->group(function () {
                Route::get('/', 'show');
                Route::post('/mensagens', 'reply')->middleware('throttle:15,1');
            });
        });
        
        Route::controller(ParceiroController::class)->prefix('hub/parceiros')->group(function () {
            Route::get('/', 'listarPorPublico')->middleware('throttle:120,1');
            Route::prefix('{parceiro}')->middleware('throttle:10,1')->group(function () {
                Route::post('/clique', 'registrarClique');
                Route::post('/conversao', 'registrarConversao');
            });
        });

        // ---------------------------------------------------------
        // PORTAL DO EMBARCADOR
        // ---------------------------------------------------------
        Route::middleware('ability:embarcador')->prefix('embarcador')->group(function () {
            
            Route::controller(EmbarcadorPerfilController::class)->prefix('perfil')->group(function () {
                Route::get('/', 'show');
                Route::put('/', 'update');
                Route::get('/documento', 'exibirDocumento');
            });
            
            Route::apiResource('locais', LocalController::class);

            Route::apiResource('cargas', EmbarcadorCargaController::class);
            Route::controller(EmbarcadorCargaController::class)->prefix('cargas')->group(function () {
                Route::get('/documento/pod', 'exibirDocumentoPod');
                Route::prefix('{carga}')->group(function () {
                    Route::post('/candidaturas/aprovar', 'aprovarCandidato')->middleware('throttle:10,1');
                    Route::post('/avaliar', 'avaliarEFinalizarEntrega')->middleware('throttle:5,1');
                    Route::post('/disputa', 'abrirDisputa')->middleware('throttle:5,1');
                    Route::get('/chat', 'getChat');
                    Route::post('/chat', 'storeChat')->middleware('throttle:20,1');
                });
            });
            
            Route::post('cargas/{carga}/checkout', [CheckoutController::class, 'gerarPagamento'])
                 ->middleware(['throttle:10,1', 'idempotency']);

            Route::controller(FaturaController::class)->prefix('faturas')->group(function () {
                Route::get('/', 'index');
                Route::get('/{fatura}', 'show');
            });
            
            Route::get('auditoria/ciot/{id}', [AuditoriaController::class, 'consultarCiot']);
            Route::post('certificado/upload', [CertificadoController::class, 'upload'])->middleware('throttle:5,1');
            Route::post('documentos/xml/parse', [DocumentoFiscalController::class, 'parse'])->middleware('throttle:10,1');
        });

        // ---------------------------------------------------------
        // PORTAL DO MOTORISTA
        // ---------------------------------------------------------
        Route::middleware('ability:motorista')->prefix('motorista')->group(function () {
            
            Route::controller(MotoristaPerfilController::class)->prefix('perfil')->group(function () {
                Route::get('/', 'show');
                Route::post('/', 'update'); 
                Route::get('/documento/{tipo}', 'exibirDocumento'); 
            });
            
            Route::post('perfil/gr/solicitar', [GrController::class, 'solicitarAnalise']);
            Route::get('carteira/extrato', [CarteiraController::class, 'extrato']);
            
            Route::controller(MotoristaCargaController::class)->prefix('cargas')->group(function () {
                Route::get('/disponiveis', 'disponiveis');
                Route::get('/minhas', 'minhasCargas');
                
                Route::prefix('{id}')->group(function () {
                    Route::post('/aceitar', 'aceitar')->middleware('throttle:10,1');
                    Route::delete('/aceitar', 'cancelarAceite');
                    Route::post('/iniciar-viagem', 'iniciarViagem');
                    Route::post('/finalizar', 'finalizarEntrega');
                });
                
                Route::prefix('{carga}')->group(function () {
                    Route::get('/chat', 'getChat');
                    Route::post('/chat', 'storeChat')->middleware('throttle:20,1');
                });
            });
        });

        // ---------------------------------------------------------
        // PORTAL ADMINISTRATIVO
        // ---------------------------------------------------------
        Route::middleware('ability:admin')->prefix('admin')->group(function () {
            
            Route::controller(AdminController::class)->group(function () {
                
                // Dashboard & Financeiro
                Route::prefix('dashboard')->group(function () {
                    Route::get('/', 'dashboardMetrics');
                    Route::get('-stats', 'getDashboardStats');
                });
                Route::get('/financeiro/extrato', 'extratoTaxas');
                
                // Fretes
                Route::prefix('fretes')->group(function () {
                    Route::get('/', 'listarFretes');
                    Route::get('/concluidos', 'fretesConcluidos');
                    Route::prefix('{id}')->group(function () {
                        Route::get('/', 'detalhesFrete');
                        Route::get('/auditoria', 'auditoriaCarga');
                    });
                });
                Route::get('/operacoes/fretes', 'listarMuralFretes');
                
                // Disputas
                Route::prefix('disputas')->group(function () {
                    Route::get('/', 'listarDisputas');
                    Route::post('/{id}/resolver', 'resolverDisputa');
                });
                Route::prefix('operacoes/disputas')->group(function () {
                    Route::get('/', 'listarDisputas');
                    Route::post('/{carga}/resolver', 'resolverDisputa');
                });

                // Auditoria
                Route::prefix('auditoria')->group(function () {
                    Route::get('/documento', 'exibirDocumentoAuditoria');
                });
                Route::get('/kyc/documento', 'exibirDocumentoKyc');
                
                // Configurações & Staff
                Route::prefix('config')->group(function () {
                    Route::get('/variaveis', 'listarVariaveis');
                    Route::put('/variaveis', 'atualizarVariaveis');
                    Route::get('/staff', 'listarStaff');
                    Route::post('/staff', 'criarStaff');
                    Route::put('/staff/{usuario}', 'atualizarStaff');
                });
                
                // Alias de Config (Retrocompatibilidade)
                Route::put('/variaveis', 'atualizarVariaveis'); 
                Route::get('/staff', 'listarStaff'); 
                Route::post('/staff', 'criarStaff'); 
                Route::put('/staff/{usuario}', 'atualizarStaff'); 
                
                // Gestão de Usuários
                Route::prefix('usuarios')->group(function () {
                    Route::get('/', 'listarTodosUsuarios');
                    Route::post('/{usuario}/analise', 'analisarUsuario');
                    Route::post('/{usuario}/status', 'alterarStatus');
                });
                Route::get('/usuarios-pendentes', 'usuariosPendentes');
                
                // Embarcadores
                Route::prefix('embarcadores')->group(function () {
                    Route::get('/', 'listarEmbarcadores');
                    Route::get('/{id}', 'detalhesEmbarcador');
                });
                Route::prefix('crm/embarcadores')->group(function () {
                    Route::get('/', 'listarEmbarcadores');
                    Route::put('/{embarcador}/contrato', 'atualizarContratoEmbarcador');
                });
                Route::put('/config/crm/embarcadores/{embarcador}/contrato', 'atualizarContratoEmbarcador');
                
                // Motoristas
                Route::prefix('motoristas')->group(function () {
                    Route::get('/', 'listarMotoristas');
                    Route::get('/{id}', 'detalhesMotorista');
                    Route::post('/{id}/kyc', 'avaliarKycMotorista');
                });
                Route::get('/crm/motoristas', 'listarMotoristas'); 
                
                // Parceiros API
                Route::prefix('parceiros-api')->group(function () {
                    Route::get('/', 'listarParceirosApi');
                    Route::post('/', 'gerarTokenParceiro');
                    Route::post('/{tokenId}/revogar', 'revogarTokenParceiro');
                });
            });
            
            Route::controller(AdminFaturamentoController::class)->prefix('faturamento')->group(function () {
                Route::get('/radar', 'radar');
                Route::get('/ciclos', 'listarCiclos');
                Route::post('/gerar', 'gerarFaturasManuais');
                Route::get('/extrato-taxas', 'extratoTaxasPlataforma');
                Route::get('/taxas-agregadas', 'taxasAgregadas');
                Route::post('/congelar/{embarcadorId}', 'congelar');
            });
            
            Route::controller(ParceiroController::class)->prefix('crm/parceiros')->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store')->middleware('throttle:30,1');
                Route::put('/{parceiro}', 'update')->middleware('throttle:30,1');
                Route::delete('/{parceiro}', 'destroy')->middleware('throttle:30,1');
            });
            
            Route::controller(TicketController::class)->prefix('suporte/tickets')->group(function () {
                Route::get('/', 'index');
                Route::prefix('{ticket}')->group(function () {
                    Route::get('/', 'show');
                    Route::post('/assumir', 'assumirTicket');
                    Route::post('/responder', 'reply');
                    Route::post('/fechar', 'fecharTicket');
                });
            });
        });

        // ---------------------------------------------------------
        // INTEGRAÇÕES DE PARCEIROS
        // ---------------------------------------------------------
        Route::middleware('ability:gr-partner')->prefix('partners/gr')->group(function () {
            Route::post('/analise/callback', [GrIntegrationController::class, 'registrarAnalise']);
        });
    });
});