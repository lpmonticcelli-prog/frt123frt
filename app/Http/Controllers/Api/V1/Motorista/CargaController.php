<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\CargaCandidatura;
use App\Services\Logistics\CandidaturaService;
use App\Events\NovaMensagemChat;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CargaController extends Controller
{
    private const ROLE_MOTORISTA = 'motorista';
    private const STATUS_PUBLICADA = 'publicada';
    private const STATUS_PENDENTE = 'pendente';
    private const STATUS_AGUARDANDO_COLETA = 'aguardando_coleta';
    private const STATUS_ALOCADA = 'alocada';
    private const STATUS_EM_TRANSITO = 'em_transito';
    private const STATUS_EM_AUDITORIA = 'em_auditoria';
    private const STATUS_CANCELADA_MOTORISTA = 'cancelada_motorista';

    public function __construct(
        private readonly CandidaturaService $candidaturaService
    ) {}

    /**
     * Sanitização Termal Estrita (Defesa contra XSS/Null Byte injetados via Socket).
     */
    private function sanitizeText(?string $payload): ?string
    {
        if ($payload === null) {
            return null;
        }
        $clean = str_replace(chr(0), '', $payload);
        $clean = strip_tags($clean);
        return htmlspecialchars($clean, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', false);
    }

    public function disponiveis(Request $request): JsonResponse
    {
        $cargas = Carga::with(['embarcador.user:id,name,email'])
            ->where('status', self::STATUS_PUBLICADA)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $cargas
        ], 200);
    }

    public function minhasCargas(Request $request): JsonResponse
    {
        $motoristaId = $request->user()->motorista->id ?? null;

        if (!$motoristaId) {
            return response()->json(['error' => 'Perfil de motorista não localizado.'], 403);
        }

        $cargas = Carga::with(['embarcador', 'candidaturas' => function ($query) use ($motoristaId) {
                $query->where('motorista_id', $motoristaId);
            }])
            ->where(function ($query) use ($motoristaId) {
                $query->where('motorista_id', $motoristaId)
                      ->orWhereHas('candidaturas', function ($sub) use ($motoristaId) {
                          $sub->where('motorista_id', $motoristaId)
                              ->where('status', self::STATUS_PENDENTE);
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => 'success', 
            'data'   => $cargas
        ], 200);
    }

    /**
     * GARGALO CRÍTICO: Matchmaking Engine.
     * Pessimistic Locking estrito e delegação correta de exceções ao Kernel.
     */
    public function aceitar(Request $request, int $id): JsonResponse
    {
        $motorista = $request->user()->motorista;

        if (!$motorista) {
            return response()->json(['error' => 'Acesso negado. Perfil incompleto.'], 403);
        }

        try {
            return DB::transaction(function () use ($id, $motorista) {
                // LOCK CONTENTION DEFENSE
                $carga = Carga::lockForUpdate()->findOrFail($id);

                if ($carga->status !== self::STATUS_PUBLICADA) {
                    throw new DomainException('Este frete não está mais aceitando lances.');
                }

                // ATOMIC CHECK
                $totalCandidaturas = DB::table('carga_candidaturas')
                    ->where('carga_id', $carga->id)
                    ->where('status', self::STATUS_PENDENTE)
                    ->count();

                if ($totalCandidaturas >= 10) {
                    throw new DomainException('O limite de 10 motoristas simultâneos para este frete foi atingido.');
                }

                $jaCandidatado = DB::table('carga_candidaturas')
                    ->where('carga_id', $carga->id)
                    ->where('motorista_id', $motorista->id)
                    ->exists();

                if ($jaCandidatado) {
                    throw new DomainException('Você já registrou um lance neste frete.');
                }

                $candidatura = $this->candidaturaService->aplicar($motorista, $carga);

                return response()->json([
                    'message' => 'Lance registrado. Aguarde a aprovação da transportadora.',
                    'data' => [
                        'candidatura_id' => $candidatura->id,
                        'status'         => $candidatura->status,
                        'expira_em'      => $candidatura->expires_at->toIso8601String()
                    ]
                ], 200);
            });

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Frete não encontrado.'], 404);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (QueryException $e) {
            // Delega falhas de lock atômico (40P01, 55P03) para o Global Exception Handler
            throw $e;
        } catch (Throwable $e) {
            Log::critical('[Matchmaking] Falha sistêmica ao registrar lance', [
                'motorista_id' => $motorista->id,
                'carga_id'     => $id,
                'motivo'       => $e->getMessage()
            ]);
            return response()->json(['error' => 'Erro interno ao processar a candidatura.'], 500);
        }
    }

    public function cancelarAceite(Request $request, int $id): JsonResponse
    {
        $motorista = $request->user()->motorista;
        
        if (!$motorista) {
            return response()->json(['error' => 'Acesso restrito.'], 403);
        }

        try {
            return DB::transaction(function () use ($id, $motorista) {
                $carga = Carga::lockForUpdate()->findOrFail($id);

                if ($carga->motorista_id === $motorista->id) {
                    $this->candidaturaService->cancelarPosAprovacao($motorista, $carga);
                    
                    return response()->json([
                        'message' => 'Carga devolvida ao mercado. ALERTA: Devido à quebra de contrato, sua métrica de SLA foi penalizada.'
                    ], 200);
                } 
                
                $candidatura = CargaCandidatura::where('carga_id', $carga->id)
                    ->where('motorista_id', $motorista->id)
                    ->where('status', self::STATUS_PENDENTE)
                    ->first();

                if ($candidatura) {
                    $candidatura->update(['status' => self::STATUS_CANCELADA_MOTORISTA]);
                    return response()->json(['message' => 'Lance removido com sucesso.'], 200);
                }

                throw new DomainException('Nenhuma candidatura ativa encontrada para este frete.');
            });

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Frete não encontrado.'], 404);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (QueryException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::critical('Falha ao cancelar lance/aceite', ['carga_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno ao processar o cancelamento.'], 500);
        }
    }

    public function iniciarViagem(Request $request, int $id): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $id) {
                $carga = Carga::lockForUpdate()->findOrFail($id);
                
                if ($carga->motorista_id !== $request->user()->motorista->id) {
                    throw new DomainException('Acesso negado. Frete alocado a terceiros.');
                }
                
                if (!in_array($carga->status, [self::STATUS_AGUARDANDO_COLETA, self::STATUS_ALOCADA], true)) {
                    throw new DomainException('A viagem não pode ser iniciada. Aguarde liberação da transportadora e Gerenciadora de Risco.');
                }

                $carga->update(['status' => self::STATUS_EM_TRANSITO]);
                
                Log::info("[Logística] Viagem iniciada", ['motorista_id' => $request->user()->motorista->id, 'carga_id' => $carga->id]);
            });

            return response()->json(['message' => 'Boa viagem! Acompanhamento por GPS ativado.'], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Frete não encontrado.'], 404);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (QueryException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Erro ao iniciar viagem', ['carga_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha interna ao inicializar telemetria do frete.'], 500);
        }
    }

    public function finalizarEntrega(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'foto_canhoto_path' => ['required', 'string', 'max:255'],
            'foto_carga_path'   => ['required', 'string', 'max:255'],
        ], [
            'required' => 'O caminho do comprovante no repositório cloud é obrigatório.'
        ]);

        $motoristaId = $request->user()->motorista->id;

        try {
            DB::transaction(function () use ($id, $motoristaId, $validated) {
                $carga = Carga::lockForUpdate()->findOrFail($id);
                
                if ($carga->motorista_id !== $motoristaId) {
                    throw new DomainException('Operação negada. Carga pertence a terceiros.', 403);
                }
                
                if ($carga->status !== self::STATUS_EM_TRANSITO) {
                    throw new DomainException('Status logístico inválido para finalização (' . $carga->status . ').', 400);
                }

                $carga->update([
                    'status' => self::STATUS_EM_AUDITORIA,
                    'foto_canhoto' => $validated['foto_canhoto_path'],
                    'foto_carga' => $validated['foto_carga_path']
                ]);

                Log::info("[POD] Finalização de Frete concluída", [
                    'motorista_id' => $motoristaId, 
                    'carga_id' => $carga->id
                ]);
            });

            return response()->json(['message' => 'Viagem finalizada. O canhoto foi enviado para a auditoria do contratante.'], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Frete não encontrado.'], 404);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        } catch (QueryException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::critical('Falha sistêmica ao finalizar frete', ['carga_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha severa de banco de dados. Tente novamente em instantes.'], 500);
        }
    }

    public function getChat(Request $request, int $id): JsonResponse
    {
        $carga = Carga::findOrFail($id);
        
        if ($carga->motorista_id !== $request->user()->motorista->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        
        $mensagens = DB::table('carga_mensagens')
            ->where('carga_id', $carga->id)
            ->orderBy('created_at', 'asc')
            ->get();
            
        return response()->json($mensagens);
    }

    public function storeChat(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $carga = Carga::findOrFail($id);
        
        if ($carga->motorista_id !== $user->motorista->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        
        $request->validate(['mensagem' => 'required|string|max:1000']);
        
        $mensagemLimpa = $this->sanitizeText($request->mensagem);

        try {
            $msgId = DB::table('carga_mensagens')->insertGetId([
                'carga_id'       => $carga->id,
                'remetente_id'   => $user->motorista->id,
                'remetente_tipo' => self::ROLE_MOTORISTA,
                'mensagem'       => $mensagemLimpa,
                'created_at'     => now(), 
                'updated_at'     => now()
            ]);

            $mensagemSalva = DB::table('carga_mensagens')->find($msgId);

            broadcast(new NovaMensagemChat($mensagemSalva, $carga->id))->toOthers();

            return response()->json($mensagemSalva, 201);
            
        } catch (Throwable $e) {
            Log::error('Erro I/O ao transmitir mensagem no chat', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno ao tentar enviar a mensagem. Verifique sua conexão.'], 500);
        }
    }
}