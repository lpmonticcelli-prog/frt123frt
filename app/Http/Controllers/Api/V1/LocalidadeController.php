<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class LocalidadeController extends Controller
{
    // Retorna a lista de Estados
    public function estados(): JsonResponse
    {
        $estados = DB::table('estados')
            ->orderBy('nome')
            ->select('uf as sigla', 'nome')
            ->get();

        return response()->json($estados);
    }

    // Retorna os Municípios respeitando o Contrato de Dados original do IBGE
    public function municipios(string $uf): JsonResponse
    {
        $estado = DB::table('estados')->where('uf', strtoupper($uf))->first();
        
        if (!$estado) {
            return response()->json(['error' => 'UF não encontrada'], 404);
        }

        // O alias 'codigo_ibge as id' garante que o Vue não quebre
        $cidades = DB::table('cidades')
            ->where('estado_id', $estado->id)
            ->orderBy('nome')
            ->select('codigo_ibge as id', 'nome') 
            ->get();

        return response()->json($cidades);
    }
}
