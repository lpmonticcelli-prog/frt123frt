<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ParceiroController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Parceiro::orderBy('ordem_exibicao')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'categoria' => 'required|in:propaganda,anuncio,contrato_comercial,produto',
            'audience' => 'required|in:motorista,embarcador,todos',
            'descricao' => 'nullable|string',
            'imagem_url' => 'nullable|url', // Proteção Anti-XSS (Impede javascript:...)
            'link_url' => 'nullable|url',   // Proteção Anti-XSS
            'conteudo_contrato' => 'nullable|string',
            'is_active' => 'boolean',
            'ordem_exibicao' => 'integer',
            'posicionamento' => 'required|in:topo,lateral,rodape,direita',
            'status_financeiro' => 'required|in:pendente,pago,isento',
            'modelo_cobranca' => 'required|in:assinatura,cpa,cpc,gratuito',
            
            // Controle da Camada Física (GPU) - Requisitado pelo Frontend
            'estatico' => 'boolean',
            'velocidade' => 'integer|min:0|max:100',
            
            // AS REGRAS REAIS DO MOTOR (Required_if: Obrigatório SE o modelo for X)
            'dias_duracao' => 'required_if:modelo_cobranca,assinatura|nullable|integer|min:1',
            'limite_cliques' => 'required_if:modelo_cobranca,cpc|nullable|integer|min:1',
            'limite_conversoes' => 'required_if:modelo_cobranca,cpa|nullable|integer|min:1',
        ]);

        // Default constraints para segurança da view
        $validated['estatico'] = $validated['estatico'] ?? false;
        $validated['velocidade'] = $validated['velocidade'] ?? 25;

        // Máquina de Estado Financeiro
        if (in_array($validated['status_financeiro'], ['pago', 'isento'])) {
            $validated['data_ativacao'] = now();
            $validated['is_active'] = true;
            if ($validated['modelo_cobranca'] === 'assinatura') {
                $validated['data_expiracao'] = now()->addDays((int) $validated['dias_duracao']);
            }
        } else {
            $validated['data_ativacao'] = null;
            $validated['data_expiracao'] = null;
            $validated['is_active'] = false;
        }

        $parceiro = DB::transaction(fn() => Parceiro::create($validated));

        Log::info("AdTech: Campanha {$validated['modelo_cobranca']} '{$parceiro->nome}' (Zona: {$parceiro->posicionamento}) ativada.");
        
        return response()->json([
            'message' => 'Campanha de tráfego injetada com sucesso!', 
            'data' => $parceiro
        ], 201);
    }

    public function update(Request $request, Parceiro $parceiro): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'string|max:255',
            'categoria' => 'in:propaganda,anuncio,contrato_comercial,produto',
            'audience' => 'in:motorista,embarcador,todos',
            'descricao' => 'nullable|string',
            'imagem_url' => 'nullable|url',
            'link_url' => 'nullable|url',
            'conteudo_contrato' => 'nullable|string',
            'is_active' => 'boolean',
            'ordem_exibicao' => 'integer',
            'posicionamento' => 'in:topo,lateral,rodape,direita',
            'status_financeiro' => 'in:pendente,pago,isento',
            'modelo_cobranca' => 'in:assinatura,cpa,cpc,gratuito',
            
            // Controle da Camada Física (GPU)
            'estatico' => 'boolean',
            'velocidade' => 'integer|min:0|max:100',
            
            'dias_duracao' => 'nullable|integer|min:1',
            'limite_cliques' => 'nullable|integer|min:1',
            'limite_conversoes' => 'nullable|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $parceiro, &$validated) {
            // Processamento Mútuo de Datas de Contrato
            if (isset($validated['status_financeiro']) && $parceiro->status_financeiro !== $validated['status_financeiro']) {
                if (in_array($validated['status_financeiro'], ['pago', 'isento'])) {
                    $validated['data_ativacao'] = now();
                    $validated['is_active'] = true;
                    if (($validated['modelo_cobranca'] ?? $parceiro->modelo_cobranca) === 'assinatura') {
                        $duracao = $validated['dias_duracao'] ?? $parceiro->dias_duracao ?? 30;
                        $validated['data_expiracao'] = now()->addDays((int) $duracao);
                    }
                } else {
                    $validated['data_ativacao'] = null;
                    $validated['data_expiracao'] = null;
                    $validated['is_active'] = false;
                }
            }

            $parceiro->update($validated);
        });

        return response()->json([
            'message' => 'Contrato/Campanha atualizado com sucesso.', 
            'data' => $parceiro
        ]);
    }

    public function destroy(Parceiro $parceiro): JsonResponse
    {
        DB::transaction(fn() => $parceiro->delete());
        
        return response()->json(['message' => 'Campanha removida da malha logística.']);
    }

    // ==========================================
    // ENDPOINTS PÚBLICOS (O MOTOR DO AD CAROUSEL)
    // ==========================================
    
    public function listarPorPublico(Request $request): JsonResponse
    {
        // 1. Captação Sanitizada do Posicionamento exigido pelo Frontend
        $posicionamento = $request->query('posicionamento');

        // 2. Determinação de Escopo do Usuário Logado
        $role = $request->user() ? $request->user()->role->slug : 'motorista';

        // 3. O construtor Query inicia com o escopo global de ativos (CPA/CPC e Data)
        $query = Parceiro::ativosPublicos()
            ->whereIn('audience', [$role, 'todos']);

        // 4. INTERCEPTADOR CRÍTICO: Isola os banners pelas zonas de exibição
        if (!empty($posicionamento)) {
            // Previne injeção SQL assegurando que seja uma das 4 zonas permitidas
            if (in_array($posicionamento, ['topo', 'lateral', 'rodape', 'direita'])) {
                $query->where('posicionamento', $posicionamento);
            } else {
                // Se a zona for inválida (ex: tentativa de scan), retorna vazio imediatamente.
                return response()->json([]);
            }
        }

        // 5. Montagem Final (Leilão)
        $parceiros = $query->orderBy('ordem_exibicao')->get();

        return response()->json($parceiros);
    }

    public function registrarClique(Parceiro $parceiro, Request $request): JsonResponse
    {
        DB::transaction(function () use ($parceiro) {
            // 🚨 Regra de Negócio Crítica: O clique só é debitado se a campanha for CPC
            if ($parceiro->modelo_cobranca === 'cpc') {
                // Se já bateu o limite, não computa clique além do faturado.
                if ($parceiro->limite_cliques === null || $parceiro->cliques_acumulados < $parceiro->limite_cliques) {
                    $parceiro->increment('cliques_acumulados');
                }
            } else {
                // Apenas para telemetria (sem cobrança)
                $parceiro->increment('cliques_acumulados');
            }
        });
        
        return response()->json(['success' => true]);
    }
    
    public function registrarConversao(Parceiro $parceiro, Request $request): JsonResponse
    {
        DB::transaction(function () use ($parceiro) {
            // 🚨 Regra de Negócio Crítica: Lead só é debitado se a campanha for CPA
            if ($parceiro->modelo_cobranca === 'cpa') {
                if ($parceiro->limite_conversoes === null || $parceiro->conversoes_acumuladas < $parceiro->limite_conversoes) {
                    $parceiro->increment('conversoes_acumuladas');
                }
            } else {
                // Apenas para telemetria
                $parceiro->increment('conversoes_acumuladas');
            }
        });
        
        return response()->json(['success' => true]);
    }
}