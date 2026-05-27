<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Carga;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * Sanitização Termal Estrita (Defesa contra XSS/Null Byte injetados no SAC).
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

    // =========================================================
    // MÓDULOS DO CLIENTE (EMBARCADOR / MOTORISTA)
    // =========================================================

    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['staff:id,name', 'carga:id,cidade_origem,cidade_destino'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assunto' => 'required|string|max:255',
            'categoria' => 'required|string',
            'carga_id' => 'nullable|exists:cargas,id',
            'mensagem' => 'required|string|max:5000',
        ]);

        $user = $request->user();

        // ZT-DEFENSE: Proteção contra Context-Spoofing e Vazamento via Engenharia Social.
        // Valida se o usuário que está abrindo o ticket de fato pertence à carga informada.
        if (!empty($validated['carga_id'])) {
            $carga = Carga::select('id', 'embarcador_id', 'motorista_id')->find($validated['carga_id']);
            
            $isEmbarcadorDono = $user->embarcador !== null && $user->embarcador->id === $carga->embarcador_id;
            $isMotoristaDono = $user->motorista !== null && $user->motorista->id === $carga->motorista_id;
            
            if (!$isEmbarcadorDono && !$isMotoristaDono) {
                Log::alert("[Security Audit] Tentativa de Context-Spoofing bloqueada. Usuário ID {$user->id} tentou vincular ticket à Carga ID {$validated['carga_id']} a qual não lhe pertence.");
                abort(403, 'Acesso negado. Você não possui vínculo logístico com esta carga.');
            }
        }

        $prioridade = 'normal';
        if ($validated['categoria'] === 'Disputa de Frete' || $request->filled('carga_id')) {
            $prioridade = 'urgente';
        } elseif ($validated['categoria'] === 'Financeiro') {
            $prioridade = 'alta';
        }

        $ticket = DB::transaction(function () use ($user, $validated, $prioridade) {
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'carga_id' => $validated['carga_id'] ?? null,
                'assunto' => $this->sanitizeText($validated['assunto']),
                'categoria' => $validated['categoria'],
                'prioridade' => $prioridade,
                'status' => 'aberto'
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'mensagem' => $this->sanitizeText($validated['mensagem'])
            ]);

            return $ticket;
        });

        return response()->json(['message' => 'Chamado aberto com sucesso. Nossa equipa responderá em breve.', 'ticket' => $ticket], 201);
    }

    public function show(Ticket $ticket, Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role');
        
        // ZT-DEFENSE: Read-IDOR Protection
        if ($user->role && in_array($user->role->slug, ['motorista', 'embarcador'], true)) {
            if ($ticket->user_id !== $user->id) {
                return response()->json(['message' => 'Acesso negado. Perímetro de segurança isolado.'], 403);
            }
        }

        $ticket->load(['user:id,name,email', 'staff:id,name', 'carga', 'messages.user:id,name']);
        
        return response()->json($ticket);
    }

    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'mensagem' => 'required|string|max:5000'
        ]);

        $user = $request->user();
        $user->loadMissing('role');
        
        $isClient = $user->role && in_array($user->role->slug, ['motorista', 'embarcador'], true);
        $isStaff = $user->role && in_array($user->role->slug, ['suporte_n1', 'compliance', 'manager'], true);

        // ZT-DEFENSE: Write-IDOR Protection para o Cliente Final
        if ($isClient && $ticket->user_id !== $user->id) {
            abort(403, 'Acesso negado. Violação de titularidade do chamado.');
        }

        // ZT-DEFENSE: Impede que um Atendente N1 responda o chamado assumido por outro especialista
        if ($isStaff && $ticket->staff_id !== null && $ticket->staff_id !== $user->id) {
            abort(403, 'Acesso negado. Este chamado já encontra-se sob custódia de outro especialista.');
        }

        DB::transaction(function () use ($ticket, $user, $validated, $isClient) {
            // ZT-DEFENSE: Lock Pessimista evita Race Conditions na Máquina de Estado
            $lockedTicket = Ticket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedTicket->status, ['resolvido', 'fechado'], true)) {
                abort(400, 'Não é possível inserir tréplicas em um chamado encerrado na auditoria.');
            }

            TicketMessage::create([
                'ticket_id' => $lockedTicket->id,
                'user_id' => $user->id,
                'mensagem' => $this->sanitizeText($validated['mensagem'])
            ]);

            $novoStatus = $isClient ? 'em_atendimento' : 'aguardando_cliente';

            $lockedTicket->update(['status' => $novoStatus]);
        });

        return response()->json(['message' => 'Resposta enviada e registrada em log.']);
    }

    // =========================================================
    // MÓDULOS DO STAFF (BACKOFFICE / N1 / ADMIN)
    // =========================================================

    public function listarFilaGlobal(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role');
        
        $query = Ticket::with(['user:id,name,email', 'carga:id,cidade_origem,cidade_destino']);

        if ($user->role && in_array($user->role->slug, ['admin', 'manager', 'compliance'], true)) {
            $query->whereIn('status', ['aberto', 'em_atendimento', 'aguardando_cliente']);
        } else {
            $query->where(function ($q) use ($user) {
                $q->whereNull('staff_id')->where('status', 'aberto')
                  ->orWhere(function ($sub) use ($user) {
                      $sub->where('staff_id', $user->id)
                          ->whereIn('status', ['em_atendimento', 'aguardando_cliente']);
                  });
            });
        }

        $tickets = $query->orderByRaw("
            CASE prioridade 
                WHEN 'urgente' THEN 1 
                WHEN 'alta' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'baixa' THEN 4 
                ELSE 5 
            END
        ")->orderBy('created_at', 'asc')->paginate(20);

        return response()->json($tickets);
    }

    public function assumirTicket(Ticket $ticket, Request $request): JsonResponse
    {
        $lockedTicket = null;

        DB::transaction(function () use ($ticket, $request, &$lockedTicket) {
            $lockedTicket = Ticket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();

            if ($lockedTicket->staff_id !== null && $lockedTicket->staff_id !== $request->user()->id) {
                abort(409, 'Conflito de Atribuição: Este ticket foi assumido por outro atendente milissegundos atrás.');
            }

            $lockedTicket->update([
                'staff_id' => $request->user()->id,
                'status' => 'em_atendimento'
            ]);
        });

        return response()->json(['message' => 'Ticket assumido com sucesso.', 'ticket' => $lockedTicket]);
    }

    public function fecharTicket(Ticket $ticket): JsonResponse
    {
        DB::transaction(function () use ($ticket) {
            $lockedTicket = Ticket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();
            $lockedTicket->update(['status' => 'resolvido']);
        });

        return response()->json(['message' => 'Ticket classificado e arquivado como resolvido.']);
    }
}