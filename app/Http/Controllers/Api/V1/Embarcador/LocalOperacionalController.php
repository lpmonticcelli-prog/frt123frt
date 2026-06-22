<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\LocalOperacional;
use Illuminate\Support\Facades\DB;

class LocalOperacionalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $embarcadorId = $request->user()->embarcador->id;
        
        $locais = LocalOperacional::where('embarcador_id', $embarcadorId)
            ->orderByDesc('is_padrao')
            ->orderBy('nome_identificador')
            ->get();

        return response()->json($locais);
    }

    public function store(Request $request): JsonResponse
    {
        $embarcadorId = $request->user()->embarcador->id;

        $validated = $request->validate([
            'nome_identificador' => 'required|string|max:100',
            'cep' => 'required|string|max:10',
            'logradouro' => 'required|string|max:255',
            'numero' => 'nullable|string|max:50',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'required|string|max:255',
            'cidade' => 'required|string|max:255',
            'uf' => 'required|string|size:2',
            'is_padrao' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            // Se este for marcado como padrão, remove o padrão dos outros
            if ($validated['is_padrao'] ?? false) {
                LocalOperacional::where('embarcador_id', $embarcadorId)->update(['is_padrao' => false]);
            }

            // Se for o primeiro endereço, força ser o padrão
            $hasEnderecos = LocalOperacional::where('embarcador_id', $embarcadorId)->exists();
            if (!$hasEnderecos) {
                $validated['is_padrao'] = true;
            }

            $local = LocalOperacional::create(array_merge($validated, ['embarcador_id' => $embarcadorId]));
            
            DB::commit();
            return response()->json($local, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Falha ao salvar local operacional.'], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $embarcadorId = $request->user()->embarcador->id;
        
        $local = LocalOperacional::where('embarcador_id', $embarcadorId)->findOrFail($id);
        
        if ($local->is_padrao) {
            return response()->json(['error' => 'Não é possível deletar o endereço padrão de faturamento.'], 422);
        }

        $local->delete();

        return response()->json(['message' => 'Local removido com sucesso.']);
    }
}