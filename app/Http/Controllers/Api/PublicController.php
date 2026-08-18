<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    /**
     * Retorna as cargas mais recentes para a Landing Page (Público)
     */
    public function cargasRecentes(): JsonResponse
    {
        // Busca as últimas 4 cargas reais publicadas no banco de dados
        $cargas = Carga::select(
                'id', 
                'cidade_origem', 
                'uf_origem', 
                'cidade_destino', 
                'uf_destino', 
                'tipo_veiculo', 
                'produto'
            )
            ->where('status', 'publicada')
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();
            
        return response()->json($cargas);
    }
}