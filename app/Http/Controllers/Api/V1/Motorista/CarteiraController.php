<?php

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use App\Models\Transacao;
use Illuminate\Http\Request;

class CarteiraController extends Controller
{
    public function extrato(Request $request)
    {
        $motoristaId = $request->user()->motorista->id ?? null;
        if (!$motoristaId) abort(403);

        $transacoes = Transacao::where('motorista_id', $motoristaId)
            ->orderBy('created_at', 'desc')
            ->get();

        $creditos = $transacoes->where('tipo', 'credito')->sum('valor');
        $debitos = $transacoes->where('tipo', 'debito')->sum('valor');
        $saldoDisponivel = $creditos - $debitos;

        return response()->json([
            'saldo_disponivel' => $saldoDisponivel,
            'transacoes' => $transacoes
        ]);
    }
}