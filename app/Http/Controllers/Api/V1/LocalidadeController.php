<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class LocalidadeController extends Controller
{
    // Retorna a lista de Estados (Contrato Atualizado: uf, nome)
    public function estados(): JsonResponse
    {
        $estados = DB::table('estados')
            ->orderBy('nome')
            ->select('uf', 'nome') // Zero aliases. O frontend agora lê 'estado.uf'
            ->get();

        return response()->json($estados);
    }

    // Retorna os Municípios otimizados para o dropdown
    public function municipios(string $uf): JsonResponse
    {
        $estado = DB::table('estados')->where('uf', strtoupper($uf))->first('id');
        
        if (!$estado) {
            return response()->json(['error' => 'UF não encontrada'], 404);
        }

        // Performance: Retorna apenas a coluna 'nome', pois o front-end ignora o ID
        $cidades = DB::table('cidades')
            ->where('estado_id', $estado->id)
            ->orderBy('nome')
            ->select('nome') 
            ->get();

        return response()->json($cidades);
    }
}