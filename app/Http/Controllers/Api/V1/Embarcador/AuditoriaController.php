<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ciot;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Jobs\LiquidarFreteJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditoriaController extends Controller
{
    /**
     * Libera o dinheiro retido na Conta Escrow para o Motorista após validação do PoD.
     */
    public function aprovarPagamento(Carga $carga, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($carga->embarcador_id !== $user->embarcador->id) {
            Log::alert("[WAF/Auditoria] Tentativa de hijacking na aprovação de pagamento.", ['user_id' => $user->id, 'carga_id' => $carga->id]);
            return response()->json(['error' => 'Acesso negado. Carga pertence a outro tenant.'], 403);
        }

        if (!in_array($carga->status, ['em_auditoria', 'em_disputa'], true)) {
            return response()->json(['error' => 'Status logístico inválido. A carga não encontra-se na mesa de auditoria.'], 400);
        }

        try {
            DB::transaction(function () use ($carga) {
                // Lock Pessimista: Impede dupla aprovação e duplo saque (Double-Spend)
                $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();
                
                if (in_array($cargaLock->status, ['finalizada', 'entregue', 'concluida'], true)) {
                    return; // Retorno silencioso (Idempotência natural em caso de duplo clique)
                }

                $cargaLock->update(['status' => 'finalizada']); 

                // Dispara o pagamento no Gateway (Pamcard/Repom) via Fila Assíncrona
                $ciot = Ciot::where('carga_id', $cargaLock->id)->lockForUpdate()->first();
                
                if ($ciot && !in_array($ciot->status, ['liquidado', 'processando_liquidacao'], true)) {
                    $ciot->update(['status' => 'processando_liquidacao']);
                    
                    // ZT-DEFENSE: A dívida técnica do código comentado foi sanada. O CIOT é injetado na queue.
                    LiquidarFreteJob::dispatch((string) $ciot->codigo_ciot)->onQueue('financeiro');
                }
            });

            Log::info("[Auditoria] Pagamento aprovado pelo Embarcador. Liquidação CIOT enfileirada. Carga ID: {$carga->id}");

            return response()->json(['message' => 'Auditoria aprovada com sucesso. O repasse financeiro foi autorizado e despachado para a carteira do motorista.']);
            
        } catch (Throwable $e) {
            Log::error("[Auditoria] Falha ao aprovar pagamento.", ['carga_id' => $carga->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha interna ao processar a liberação financeira.'], 500);
        }
    }

    /**
     * Congela os fundos na Conta Escrow e aciona a Mesa de Operações (NOC).
     */
    public function abrirDisputa(Carga $carga, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['error' => 'Acesso negado. Carga pertence a outro tenant.'], 403);
        }
        
        $request->validate(['motivo' => 'required|string|max:1000']);

        try {
            DB::transaction(function () use ($carga, $request, $user) {
                $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();
                
                if ($cargaLock->status !== 'em_auditoria') {
                    abort(400, 'Apenas cargas aguardando auditoria podem ser contestadas.');
                }

                $cargaLock->update(['status' => 'em_disputa']);

                // Trava o CIOT para impedir liquidação acidental ou execução por timeout de SLA
                $ciot = Ciot::where('carga_id', $cargaLock->id)->lockForUpdate()->first();
                if ($ciot && $ciot->status !== 'liquidado') {
                    $ciot->update(['status' => 'bloqueado_disputa']);
                }

                $ticket = Ticket::create([
                    'user_id' => $user->id,
                    'carga_id' => $cargaLock->id,
                    'categoria' => 'Disputa de Frete',
                    'assunto' => 'Reprovação de PoD / Auditoria',
                    'status' => 'aberto',
                    'prioridade' => 'urgente'
                ]);
                
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    // ZT-DEFENSE: Sanitização HTML5 obrigatória na persistência em DB de texto livre
                    'mensagem' => "O Embarcador abriu disputa após analisar o comprovante. Motivo alegado: " . htmlspecialchars($request->motivo, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                ]);
            });

            Log::warning("[Auditoria] Disputa aberta pelo Embarcador. Fundos congelados. Carga ID: {$carga->id}");

            return response()->json(['message' => 'Disputa aberta. A operação foi congelada e a mesa de operações (NOC) acionada.']);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            Log::error("[Auditoria] Falha ao abrir disputa.", ['carga_id' => $carga->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno ao processar o congelamento financeiro.'], 500);
        }
    }
}