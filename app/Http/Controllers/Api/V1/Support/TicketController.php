<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    // =========================================================
    // MÓDULOS DO CLIENTE (EMBARCADOR / MOTORISTA)
    // =========================================================

    public function index(Request $request)
    {
        $tickets = Ticket::with(['staff:id,name', 'carga:id,cidade_origem,cidade_destino'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assunto' => 'required|string|max:255',
            'categoria' => 'required|string',
            'carga_id' => 'nullable|exists:cargas,id',
            'mensagem' => 'required|string',
        ]);

        $prioridade = 'normal';
        if ($validated['categoria'] === 'Disputa de Frete' || $request->filled('carga_id')) {
            $prioridade = 'urgente';
        } elseif ($validated['categoria'] === 'Financeiro') {
            $prioridade = 'alta';
        }

        $ticket = DB::transaction(function () use ($request, $validated, $prioridade) {
            $ticket = Ticket::create([
                'user_id' => $request->user()->id,
                'carga_id' => $validated['carga_id'] ?? null,
                'assunto' => $validated['assunto'],
                'categoria' => $validated['categoria'],
                'prioridade' => $prioridade,
                'status' => 'aberto'
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'mensagem' => $validated['mensagem']
            ]);

            return $ticket;
        });

        return response()->json(['message' => 'Chamado aberto com sucesso. Nossa equipa responderá em breve.', 'ticket' => $ticket], 201);
    }

    public function show(Ticket $ticket, Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');
        
        if ($user->role && in_array($user->role->slug, ['motorista', 'embarcador'])) {
            if ($ticket->user_id !== $user->id) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }
        }

        $ticket->load(['user:id,name,email', 'staff:id,name', 'carga', 'messages.user:id,name']);
        
        return response()->json($ticket);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'mensagem' => 'required|string'
        ]);

        $user = $request->user();
        $user->loadMissing('role');

        DB::transaction(function () use ($ticket, $user, $validated) {
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'mensagem' => $validated['mensagem']
            ]);

            $novoStatus = ($user->role && in_array($user->role->slug, ['motorista', 'embarcador']))
                ? 'em_atendimento' 
                : 'aguardando_cliente';

            $ticket->update(['status' => $novoStatus]);
        });

        return response()->json(['message' => 'Resposta enviada.']);
    }

    // =========================================================
    // MÓDULOS DO STAFF (BACKOFFICE / N1 / ADMIN)
    // =========================================================

    public function listarFilaGlobal(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');
        
        $query = Ticket::with(['user:id,name,email', 'carga:id,cidade_origem,cidade_destino']);

        if ($user->role && in_array($user->role->slug, ['admin', 'manager', 'compliance'])) {
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

    public function assumirTicket(Ticket $ticket, Request $request)
    {
        if ($ticket->staff_id !== null && $ticket->staff_id !== $request->user()->id) {
            return response()->json(['message' => 'Este ticket já foi assumido por outro atendente.'], 409);
        }

        $ticket->update([
            'staff_id' => $request->user()->id,
            'status' => 'em_atendimento'
        ]);

        return response()->json(['message' => 'Ticket assumido com sucesso.', 'ticket' => $ticket]);
    }

    public function fecharTicket(Ticket $ticket)
    {
        $ticket->update(['status' => 'resolvido']);
        return response()->json(['message' => 'Ticket classificado como resolvido.']);
    }
}