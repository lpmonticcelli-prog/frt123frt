<?php

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    public function aprovarPagamento(Carga $carga, Request $request)
    {
        $user = $request->user();

        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }
        if ($carga->status !== 'em_auditoria') {
            return response()->json(['message' => 'A carga não está na mesa de auditoria.'], 400);
        }

        DB::transaction(function () use ($carga) {
            // CIRURGIA APLICADA: Status "finalizada" para alinhar perfeitamente com o Faturamento do Admin
            $carga->update(['status' => 'finalizada']); 

            // LiquidarFreteJob::dispatch($carga->id)->onQueue('financeiro');
        });

        return response()->json(['message' => 'Auditoria aprovada com sucesso.']);
    }

    public function abrirDisputa(Carga $carga, Request $request)
    {
        $user = $request->user();

        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }
        
        $request->validate(['motivo' => 'required|string|max:500']);

        DB::transaction(function () use ($carga, $request, $user) {
            $carga->update(['status' => 'em_disputa']);

            Ticket::create([
                'user_id' => $user->id,
                'carga_id' => $carga->id,
                'categoria' => 'Disputa de Frete',
                'assunto' => 'Reprovação de PoD / Auditoria',
                'mensagem' => "O Embarcador abriu disputa após analisar o comprovante. Motivo alegado: " . $request->motivo,
                'status' => 'aberto',
                'prioridade' => 'alta'
            ]);
        });

        return response()->json(['message' => 'Disputa aberta. Operação congelada.']);
    }
}