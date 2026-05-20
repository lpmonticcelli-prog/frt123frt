<?php

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ciot;
use App\Models\CargaCandidatura;
use App\Contracts\PefGatewayInterface;
use App\Services\Logistics\CandidaturaService;
use App\Events\NovaMensagemChat; // 🔥 IMPORTAÇÃO DO EVENTO WEBSOCKET
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CargaController extends Controller
{
    protected CandidaturaService $candidaturaService;

    public function __construct(CandidaturaService $candidaturaService)
    {
        $this->candidaturaService = $candidaturaService;
    }

    public function disponiveis(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => Carga::with(['embarcador.user:id,name,email'])
                ->where('status', 'publicada')
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        ], 200);
    }

    public function minhasCargas(Request $request)
    {
        $motoristaId = $request->user()->motorista->id ?? null;
        if (!$motoristaId) {
            return response()->json(['error' => 'Perfil de motorista não localizado.'], 403);
        }

        // CORREÇÃO: Puxa cargas alocadas OU cargas onde o motorista tem lances pendentes (para não sumirem)
        $cargas = Carga::with(['embarcador', 'candidaturas'])
            ->where(function ($query) use ($motoristaId) {
                $query->where('motorista_id', $motoristaId)
                      ->orWhereHas('candidaturas', function ($sub) use ($motoristaId) {
                          $sub->where('motorista_id', $motoristaId)->where('status', 'pendente');
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json(['status' => 'success', 'data' => $cargas], 200);
    }

    public function aceitar(Request $request, $id)
    {
        $motorista = $request->user()->motorista;
        if (!$motorista) {
            return response()->json(['error' => 'Acesso negado. Crie o seu perfil.'], 403);
        }

        $carga = Carga::findOrFail($id);

        try {
            // Delegação estrita para a Camada de Domínio
            $candidatura = $this->candidaturaService->aplicar($motorista, $carga);

            return response()->json([
                'message' => 'Candidatura registrada com sucesso! Aguarde a aprovação do embarcador.',
                'data' => [
                    'candidatura_id' => $candidatura->id,
                    'status' => $candidatura->status,
                    'expira_em' => $candidatura->expires_at->toIso8601String()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::warning("[Bidding] Falha na candidatura: Motorista {$motorista->id} na Carga {$carga->id}. Motivo: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    public function cancelarAceite(Request $request, $id, PefGatewayInterface $pefGateway)
    {
        $motorista = $request->user()->motorista;
        $carga = Carga::findOrFail($id);

        try {
            // Cenário 1: Quebra de Contrato (Motorista já aprovado cancela a viagem)
            if ($carga->motorista_id === $motorista->id) {
                
                $this->candidaturaService->cancelarPosAprovacao($motorista, $carga);

                // Expurgo de recursos de terceiros (CIOT)
                $ciot = Ciot::where('carga_id', $carga->id)->first();
                if ($ciot) {
                    $pefGateway->cancelarCiot($ciot->codigo_ciot);
                    $ciot->update(['status' => 'cancelado']);
                    $ciot->delete();
                }

                return response()->json([
                    'message' => 'Carga devolvida. ALERTA DE COMPLIANCE: Devido ao cancelamento após aprovação, seu perfil foi suspenso por 24 horas.'
                ], 200);
            } 
            
            // Cenário 2: Retirada pacífica de Lance (Candidatura Pendente)
            $candidatura = CargaCandidatura::where('carga_id', $carga->id)
                ->where('motorista_id', $motorista->id)
                ->where('status', 'pendente')
                ->first();

            if ($candidatura) {
                $candidatura->update(['status' => 'cancelada_motorista']);
                return response()->json(['message' => 'Candidatura retirada com sucesso.'], 200);
            }

            return response()->json(['error' => 'Nenhuma candidatura ativa ou carga atribuída encontrada para cancelamento.'], 404);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function iniciarViagem(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            // Lock Pessimista necessário para garantir integridade atômica da mudança de state
            $carga = Carga::lockForUpdate()->findOrFail($id);
            
            if ($carga->motorista_id !== $request->user()->motorista->id) {
                abort(403, 'Operação negada. Propriedade da carga violada.');
            }
            
            // Ajustado para permitir os status corretos do Bidding, incluindo o MOCK (em_analise_gr)
            if (!in_array($carga->status, ['aguardando_coleta', 'aceita', 'processando_aceite', 'em_analise_gr', 'alocada'])) {
                abort(400, 'Status inválido. Aguarde a liberação do CIOT e da GR.');
            }

            $carga->update(['status' => 'em_transito']);
        });

        return response()->json(['message' => 'Viagem iniciada.'], 200);
    }

    public function finalizarEntrega(Request $request, $id)
    {
        // Upload nativo atômico (salva na pasta public/pod do servidor)
        $request->validate([
            'foto_canhoto' => 'required|image|max:10240',
            'foto_carga'   => 'required|image|max:10240',
        ]);

        DB::transaction(function () use ($request, $id) {
            $carga = Carga::lockForUpdate()->findOrFail($id);
            
            if ($carga->motorista_id !== $request->user()->motorista->id) {
                abort(403, 'Operação negada.');
            }
            
            if ($carga->status !== 'em_transito') {
                abort(400, 'Status logístico inválido para finalização.');
            }

            $pathCanhoto = $request->file('foto_canhoto')->store("pod/carga_{$carga->id}", 'public');
            $pathCarga = $request->file('foto_carga')->store("pod/carga_{$carga->id}", 'public');

            $carga->update([
                'status' => 'em_auditoria',
                'foto_canhoto' => url('storage/'.$pathCanhoto),
                'foto_carga' => url('storage/'.$pathCarga)
            ]);
        });

        return response()->json(['message' => 'Entrega finalizada. A carga agora aguarda avaliação de reputação pelo Embarcador.'], 200);
    }

    // =====================================================================
    // CHAT ZERO TRUST (WEBSOCKETS EM TEMPO REAL)
    // =====================================================================
    public function getChat(Request $request, $id)
    {
        $carga = Carga::findOrFail($id);
        if ($carga->motorista_id !== $request->user()->motorista->id) return response()->json(['error' => 'Acesso negado.'], 403);
        $mensagens = DB::table('carga_mensagens')->where('carga_id', $carga->id)->orderBy('created_at', 'asc')->get();
        return response()->json($mensagens);
    }

    public function storeChat(Request $request, $id)
    {
        $user = $request->user();
        $carga = Carga::findOrFail($id);
        
        if ($carga->motorista_id !== $user->motorista->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        
        $request->validate(['mensagem' => 'required|string|max:1000']);
        $mensagemLimpa = $request->mensagem;

        $msgId = DB::table('carga_mensagens')->insertGetId([
            'carga_id' => $carga->id,
            'remetente_id' => $user->motorista->id,
            'remetente_tipo' => 'motorista',
            'mensagem' => $mensagemLimpa,
            'created_at' => now(), 
            'updated_at' => now()
        ]);

        // Puxa a mensagem exata que acabou de ser salva
        $mensagemSalva = DB::table('carga_mensagens')->find($msgId);

        // 🔥 O ALTO-FALANTE: Avisa todos no canal do WebSocket (menos o próprio emissor) que há uma nova mensagem
        broadcast(new NovaMensagemChat($mensagemSalva, $carga->id))->toOthers();

        return response()->json($mensagemSalva, 201);
    }
}