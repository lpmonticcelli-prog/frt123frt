<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Fatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaturaController extends Controller
{
    /**
     * Retorna a listagem de faturas de forma otimizada (sem payload JSON de cargas).
     */
    public function index(Request $request): JsonResponse
    {
        $embarcador = $request->user()->embarcador;

        if (!$embarcador) {
            return response()->json(['message' => 'Acesso negado. Perfil não localizado.'], 403);
        }

        // VETO: Jamais carregue o JSON 'detalhes_cargas' em paginação. Isso causa gargalo de I/O.
        $faturas = Fatura::select([
                'id', 'embarcador_id', 'mes_referencia', 'valor_total', 
                'status', 'data_vencimento', 'data_pagamento', 'created_at'
            ])
            ->where('embarcador_id', $embarcador->id)
            ->orderByDesc('created_at')
            ->paginate(12);

        return response()->json($faturas);
    }

    /**
     * Retorna o detalhe da fatura, incluindo a composição do JSON, protegido por tenant isolation.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $embarcador = $request->user()->embarcador;

        if (!$embarcador) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        // Proteção implícita contra IDOR e injeção de SQL via Eloquent ORM.
        $fatura = Fatura::where('embarcador_id', $embarcador->id)->findOrFail($id);
        
        return response()->json($fatura);
    }
}