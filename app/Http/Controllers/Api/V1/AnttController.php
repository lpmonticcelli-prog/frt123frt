<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnttTabela;
use Illuminate\Http\JsonResponse;

class AnttController extends Controller
{
    public function calcular(Request $request): JsonResponse
    {
        // 1. Valida se o frontend mandou os dados corretos
        $request->validate([
            'distancia_km' => 'required|numeric|min:1',
            'eixos' => 'required|integer|min:2|max:9',
            'tipo_carga' => 'required|string'
        ]);

        // 2. Busca a tabela exata no seu banco de dados
        $tabela = AnttTabela::where('eixos', $request->eixos)
            ->where('tipo_carga', $request->tipo_carga)
            ->first();

        // Se o usuário colocar um caminhão que não existe na lei (ex: 8 eixos)
        if (!$tabela) {
            return response()->json([
                'error' => 'Tabela ANTT indisponível para esta combinação de Carga e Eixos.'
            ], 404);
        }

        // 3. A Matemática Oficial da ANTT
        $freteMinimo = ($request->distancia_km * $tabela->coeficiente_deslocamento) + $tabela->coeficiente_carga_descarga;

        // 4. Devolve o valor formatado para o Vue.js
        return response()->json([
            'distancia_km' => $request->distancia_km,
            'valor_minimo_antt' => round($freteMinimo, 2),
            'detalhes' => [
                'custo_km' => $tabela->coeficiente_deslocamento,
                'custo_fixo' => $tabela->coeficiente_carga_descarga
            ]
        ]);
    }
}