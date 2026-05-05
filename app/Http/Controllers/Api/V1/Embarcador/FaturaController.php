<?php

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;

use App\Models\Fatura;
use Illuminate\Http\Request;

class FaturaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if (!$user->role || $user->role->slug !== 'embarcador') {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $faturas = Fatura::withCount('cargas')
            ->where('embarcador_id', $user->embarcador->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($faturas);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $user->loadMissing('embarcador');

        $fatura = Fatura::with(['cargas' => function($query) {
            $query->select('id', 'fatura_id', 'cidade_origem', 'cidade_destino', 'valor_frete', 'taxa_plataforma');
        }])->findOrFail($id);

        if ($fatura->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($fatura);
    }
}