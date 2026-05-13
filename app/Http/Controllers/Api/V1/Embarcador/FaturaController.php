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

        if (!$user->role || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        // Removemos o withCount('cargas') porque a informação agora viaja no JSON 'detalhes_cargas'
        $faturas = Fatura::where('embarcador_id', $user->embarcador->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json($faturas);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if (!$user->role || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        // Removemos o with(['cargas']) pois o frontend já lê diretamente do JSON nativo da Fatura
        $fatura = Fatura::where('embarcador_id', $user->embarcador->id)->findOrFail($id);
        
        return response()->json($fatura);
    }
}