<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParceiroController extends Controller
{
    // ==========================================
    // MÓDULOS DO BACKOFFICE (ADMIN)
    // ==========================================
    
    public function index()
    {
        return response()->json(Parceiro::orderBy('ordem_exibicao')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'categoria' => 'required|in:propaganda,anuncio,contrato_comercial,produto',
            'audience' => 'required|in:motorista,embarcador,todos',
            'descricao' => 'nullable|string',
            'imagem_url' => 'nullable|string',
            'link_url' => 'nullable|url',
            'conteudo_contrato' => 'nullable|string',
            'is_active' => 'boolean',
            'ordem_exibicao' => 'integer',
            // Validações Financeiras
            'valor_cobrado' => 'nullable|numeric|min:0',
            'modelo_cobranca' => 'required|in:unico,mensal,anual,por_clique',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'codigo_contrato_externo' => 'nullable|string|max:100',
            'posicionamento' => 'required|string',
            'status_financeiro' => 'required|in:em_dia,pendente,cancelado,cortesia',
        ]);

        // Trata valores nulos de dinheiro
        $validated['valor_cobrado'] = $validated['valor_cobrado'] ?? 0;

        $parceiro = Parceiro::create($validated);

        Log::info("AdTech: Contrato Comercial '{$parceiro->nome}' criado pelo Admin ID " . auth()->id());

        return response()->json(['message' => 'Contrato/Parceiro cadastrado com sucesso!', 'data' => $parceiro], 201);
    }

    public function update(Request $request, Parceiro $parceiro)
    {
        $validated = $request->validate([
            'nome' => 'string|max:255',
            'categoria' => 'in:propaganda,anuncio,contrato_comercial,produto',
            'audience' => 'in:motorista,embarcador,todos',
            'descricao' => 'nullable|string',
            'imagem_url' => 'nullable|string',
            'link_url' => 'nullable|url',
            'conteudo_contrato' => 'nullable|string',
            'is_active' => 'boolean',
            'ordem_exibicao' => 'integer',
            // Validações Financeiras
            'valor_cobrado' => 'nullable|numeric|min:0',
            'modelo_cobranca' => 'in:unico,mensal,anual,por_clique',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'codigo_contrato_externo' => 'nullable|string|max:100',
            'posicionamento' => 'string',
            'status_financeiro' => 'in:em_dia,pendente,cancelado,cortesia',
        ]);

        if (isset($validated['valor_cobrado'])) {
            $validated['valor_cobrado'] = $validated['valor_cobrado'] ?? 0;
        }

        $parceiro->update($validated);

        return response()->json(['message' => 'Contrato atualizado com sucesso!', 'data' => $parceiro]);
    }

    public function destroy(Parceiro $parceiro)
    {
        $parceiro->delete();
        return response()->json(['message' => 'Contrato removido da base ativa.']);
    }

    // ==========================================
    // ENDPOINTS PÚBLICOS (MOTORISTAS/EMBARCADORES)
    // ==========================================
    
    public function listarPorPublico(Request $request)
    {
        $user = $request->user();
        $role = $user->role->slug;

        // INTELIGÊNCIA TEMPORAL E FINANCEIRA: Só mostra se estiver ativo, dentro da validade e sem calote
        $parceiros = Parceiro::where('is_active', true)
            ->whereIn('audience', [$role, 'todos'])
            ->whereIn('status_financeiro', ['em_dia', 'cortesia'])
            ->where(function($query) {
                $query->whereNull('data_fim')
                      ->orWhere('data_fim', '>=', now()->toDateString());
            })
            ->where(function($query) {
                $query->whereNull('data_inicio')
                      ->orWhere('data_inicio', '<=', now()->toDateString());
            })
            ->orderBy('ordem_exibicao')
            ->get();

        return response()->json($parceiros);
    }

    // MOTOR DE RASTREAMENTO DE ENGAJAMENTO
    public function registrarClique(Parceiro $parceiro, Request $request)
    {
        // Incrementa de forma atômica no banco de dados para evitar perda de contagem em acessos simultâneos
        $parceiro->increment('cliques_acumulados');

        // Em uma plataforma gigante, você pode gravar um log secundário com o ID do usuário que clicou
        Log::info("AdTech Rastreio: Clique registrado no parceiro ID {$parceiro->id} pelo Usuário ID " . $request->user()->id);

        return response()->json(['success' => true]);
    }
}