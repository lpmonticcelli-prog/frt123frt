<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ciot;
use App\Models\CargaCandidatura;
use App\Contracts\PefGatewayInterface;
use App\Services\Logistics\CandidaturaService;
use App\Events\NovaMensagemChat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
    private const STATUS_CANCELADO = 'cancelado';

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
        return response()->json([
            'status' => 'success',
            'data' => Carga::with(['embarcador.user:id,name,email'])
                ->where('status', self::STATUS_PUBLICADA)
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        ], 200);
    }

    public function minhasCargas(Request $request): JsonResponse
    {
        $motoristaId = $request->user()->motorista->id ?? null;
        if (!$motoristaId) {
            return response()->json(['error' => 'Perfil de motorista não localizado.'], 403);
        }

        // Recupera cargas alocadas OU lances pendentes em uma única query
        $cargas = Carga::with(['embarcador', 'candidaturas'])
            ->where(function ($query) use ($motoristaId) {
                $query->where('motorista_id', $motoristaId)
                      ->orWhereHas('candidaturas', function ($sub) use ($motoristaId) {
                          $sub->where('motorista_id', $motoristaId)->where('status', self::STATUS_PENDENTE);
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json(['status' => 'success', 'data' => $cargas], 200);
    }

    public function aceitar(Request $request, int $id): JsonResponse
    {
        $motorista = $request->user()->motorista;
        if (!$motorista) {
            return response()->json(['error' => 'Acesso negado. Perfil incompleto.'], 403);
        }

        try {
            $carga = Carga::findOrFail($id);
            
            // Delegação estrita para a Camada de Domínio Logístico
            $candidatura = $this->candidaturaService->aplicar($motorista, $carga);

            return response()->json([
                'message' => 'Lance registrado. Aguarde a aprovação da transportadora.',
                'data' => [
                    'candidatura_id' => $candidatura->id,
                    'status' => $candidatura->status,
                    'expira_em' => $candidatura->expires_at->toIso8601String()
                ]
            ], 200);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
             return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            Log::warning("[Bidding] Falha de candidatura interceptada", [
                'motorista_id' => $motorista->id,
                'carga_id' => $id,
                'motivo' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    public function cancelarAceite(Request $request, int $id, PefGatewayInterface $pefGateway): JsonResponse
    {
        $motorista = $request->user()->motorista;
        
        if (!$motorista) {
            return response()->json(['error' => 'Acesso restrito.'], 403);
        }

        try {
            $carga = Carga::findOrFail($id);

            // Cenário 1: Quebra de Contrato (Motorista cancela após ser escolhido e faturado no CIOT)
            if ($carga->motorista_id === $motorista->id) {
                
                DB::beginTransaction();
                
                $this->candidaturaService->cancelarPosAprovacao($motorista, $carga);

                // Expurgo de recursos fiduciários
                $ciot = Ciot::where('carga_id', $carga->id)->lockForUpdate()->first();
                if ($ciot) {
                    $pefGateway->cancelarCiot($ciot->codigo_ciot);
                    $ciot->update(['status' => self::STATUS_CANCELADO]);
                    $ciot->delete(); // Soft delete por conformidade legal
                }
                
                DB::commit();

                return response()->json([
                    'message' => 'Carga devolvida ao mercado. ALERTA: Devido à quebra de contrato, sua conta foi penalizada/suspensa.'
                ], 200);
            } 
            
            // Cenário 2: Retirada pacífica de Lance (Candidatura Pendente)
            $candidatura = CargaCandidatura::where('carga_id', $carga->id)
                ->where('motorista_id', $motorista->id)
                ->where('status', self::STATUS_PENDENTE)
                ->first();

            if ($candidatura) {
                $candidatura->update(['status' => self::STATUS_CANCELADA_MOTORISTA]);
                return response()->json(['message' => 'Lance removido com sucesso.'], 200);
            }

            return response()->json(['error' => 'Nenhuma candidatura ativa encontrada para este frete.'], 404);

        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::critical('Falha ao cancelar aceite', ['carga_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno ao processar o cancelamento.'], 500);
        }
    }

    public function iniciarViagem(Request $request, int $id): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $id) {
                $carga = Carga::where('id', $id)->lockForUpdate()->firstOrFail();
                
                if ($carga->motorista_id !== $request->user()->motorista->id) {
                    abort(403, 'Acesso negado. Frete alocado a terceiros.');
                }
                
                // Validação de Compliance Fiduciária e Risco (GR)
                if (!in_array($carga->status, [self::STATUS_AGUARDANDO_COLETA, self::STATUS_ALOCADA], true)) {
                    abort(400, 'A viagem não pode ser iniciada. Aguarde liberação jurídica (Trans Sat) ou fiduciária (CIOT).');
                }

                $carga->update(['status' => self::STATUS_EM_TRANSITO]);
                
                Log::info("[Logística] Viagem iniciada", ['motorista_id' => $request->user()->motorista->id, 'carga_id' => $carga->id]);
            });

            return response()->json(['message' => 'Boa viagem! Acompanhamento por GPS ativado.'], 200);
            
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            Log::error('Erro ao iniciar viagem', ['carga_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha interna ao inicializar telemetria do frete.'], 500);
        }
    }

    /**
     * O Cofre: Finalização Crítica de Entrega (Comprovantes).
     */
    public function finalizarEntrega(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'foto_canhoto' => 'required|image|max:10240',
            'foto_carga'   => 'required|image|max:10240',
        ], [
            'image' => 'Evidências devem ser obrigatoriamente imagens fotográficas (JPG/PNG).',
            'max' => 'Para evitar exaustão do servidor, envie fotos com até 10MB.'
        ]);

        $motoristaId = $request->user()->motorista->id;
        $pathCanhoto = null;
        $pathCarga = null;

        try {
            DB::beginTransaction();
            
            $carga = Carga::where('id', $id)->lockForUpdate()->firstOrFail();
            
            if ($carga->motorista_id !== $motoristaId) {
                abort(403, 'Operação negada.');
            }
            
            if ($carga->status !== self::STATUS_EM_TRANSITO) {
                abort(400, 'Status logístico inválido para finalização (' . $carga->status . ').');
            }

            // Operação LFI Secure & Cloud Agnostic
            $prefix = "pod/carga_{$carga->id}";
            $pathCanhoto = $request->file('foto_canhoto')->store($prefix);
            $pathCarga = $request->file('foto_carga')->store($prefix);

            if (!$pathCanhoto || !$pathCarga) {
                throw new \RuntimeException('Falha no subsistema de armazenamento. As evidências não foram persistidas.');
            }

            $carga->update([
                'status' => self::STATUS_EM_AUDITORIA,
                'foto_canhoto' => $pathCanhoto,
                'foto_carga' => $pathCarga
            ]);
            
            DB::commit();

            Log::info("[POD] Finalização de Frete concluída", [
                'motorista_id' => $motoristaId, 
                'carga_id' => $carga->id
            ]);

            return response()->json(['message' => 'Viagem finalizada. O canhoto foi enviado para a auditoria do contratante.'], 200);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            DB::rollBack();
            
            // Reversão de I/O (Evita lixo no disco/S3)
            if ($pathCanhoto && Storage::exists($pathCanhoto)) Storage::delete($pathCanhoto);
            if ($pathCarga && Storage::exists($pathCarga)) Storage::delete($pathCarga);

            Log::critical('Falha catastrófica ao finalizar frete', ['carga_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha severa de rede ou armazenamento. Tente enviar novamente em instantes.'], 500);
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
                'carga_id' => $carga->id,
                'remetente_id' => $user->motorista->id,
                'remetente_tipo' => self::ROLE_MOTORISTA,
                'mensagem' => $mensagemLimpa,
                'created_at' => now(), 
                'updated_at' => now()
            ]);

            $mensagemSalva = DB::table('carga_mensagens')->find($msgId);

            // Transmissão Assíncrona para o Frontend Logístico (Pusher/Reverb)
            broadcast(new NovaMensagemChat($mensagemSalva, $carga->id))->toOthers();

            return response()->json($mensagemSalva, 201);
            
        } catch (Throwable $e) {
            Log::error('Erro ao transmitir mensagem no chat (Motorista)', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno ao tentar enviar a mensagem. Verifique sua conexão.'], 500);
        }
    }
}