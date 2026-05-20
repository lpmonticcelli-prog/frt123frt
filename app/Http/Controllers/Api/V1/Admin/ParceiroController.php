<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ParceiroController extends Controller
{
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
            'posicionamento' => 'required|in:topo,lateral,rodape',
            'status_financeiro' => 'required|in:pendente,pago,isento',
            'modelo_cobranca' => 'required|in:assinatura,cpa,cpc,gratuito',
            
            // AS REGRAS REAIS DO MOTOR (Required_if: Obrigatório SE o modelo for X)
            'dias_duracao' => 'required_if:modelo_cobranca,assinatura|nullable|integer|min:1',
            'limite_cliques' => 'required_if:modelo_cobranca,cpc|nullable|integer|min:1',
            'limite_conversoes' => 'required_if:modelo_cobranca,cpa|nullable|integer|min:1',
        ]);

        if (in_array($validated['status_financeiro'], ['pago', 'isento'])) {
            $validated['data_ativacao'] = now();
            $validated['is_active'] = true;
            if ($validated['modelo_cobranca'] === 'assinatura') {
                $validated['data_expiracao'] = now()->addDays($validated['dias_duracao']);
            }
        } else {
            $validated['data_ativacao'] = null;
            $validated['data_expiracao'] = null;
            $validated['is_active'] = false;
        }

        $parceiro = DB::transaction(fn() => Parceiro::create($validated));

        Log::info("AdTech: Campanha {$validated['modelo_cobranca']} '{$parceiro->nome}' criada.");
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
            'posicionamento' => 'in:topo,lateral,rodape',
            'status_financeiro' => 'in:pendente,pago,isento',
            'modelo_cobranca' => 'in:assinatura,cpa,cpc,gratuito',
            
            'dias_duracao' => 'nullable|integer|min:1',
            'limite_cliques' => 'nullable|integer|min:1',
            'limite_conversoes' => 'nullable|integer|min:1',
        ]);

        if (isset($validated['status_financeiro']) && $parceiro->status_financeiro !== $validated['status_financeiro']) {
            if (in_array($validated['status_financeiro'], ['pago', 'isento'])) {
                $validated['data_ativacao'] = now();
                $validated['is_active'] = true;
                if (($validated['modelo_cobranca'] ?? $parceiro->modelo_cobranca) === 'assinatura') {
                    $duracao = $validated['dias_duracao'] ?? $parceiro->dias_duracao ?? 30;
                    $validated['data_expiracao'] = now()->addDays($duracao);
                }
            } else {
                $validated['data_ativacao'] = null;
                $validated['data_expiracao'] = null;
                $validated['is_active'] = false;
            }
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
    // ENDPOINTS PÚBLICOS (USANDO O MOTOR REAL)
    // ==========================================
    
    public function listarPorPublico(Request $request)
    {
        $role = $request->user()->role->slug;
        // O scopeAtivosPublicos() executa a query SQL gigante de forma limpa!
        $parceiros = Parceiro::ativosPublicos()
            ->whereIn('audience', [$role, 'todos'])
            ->orderBy('ordem_exibicao')
            ->get();

        return response()->json($parceiros);
    }

    public function registrarClique(Parceiro $parceiro, Request $request)
    {
        $parceiro->increment('cliques_acumulados');
        return response()->json(['success' => true]);
    }
    
    // NOVO: Endpoint para o sistema chamar quando uma conversão (CPA) ocorrer (ex: Frete fechado)
    public function registrarConversao(Parceiro $parceiro, Request $request)
    {
        $parceiro->increment('conversoes_acumuladas');
        return response()->json(['success' => true]);
    }
}