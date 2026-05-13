<?php

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ciot;
use App\Http\Requests\StoreCargaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\LiquidarFreteJob;

class CargaController extends Controller
{
    /**
     * CIRURGIA APLICADA: Criação atómica síncrona.
     * Abortamos o uso de Job para criar a carga inicial. O Embarcador precisa de garantia I/O imediata.
     */
    public function store(StoreCargaRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        // Operação ACID: Ou grava tudo, ou falha tudo e devolve erro 500 para o Vue.js
        $carga = DB::transaction(function () use ($validated, $user, $request) {
            
            $taxaPlataforma = round($validated['valor_frete'] * 0.05, 2);

            // 1. Cria a Carga Oficialmente no PostgreSQL
            $novaCarga = Carga::create([
                'embarcador_id' => $user->embarcador->id,
                'produto' => $validated['produto'],
                'especie' => $validated['especie'],
                'peso_kg' => $validated['peso_kg'],
                'cubagem_m3' => $validated['cubagem_m3'] ?? null,
                'tipo_veiculo' => $validated['tipo_veiculo'],
                'tipo_carroceria' => $validated['tipo_carroceria'],
                'cidade_origem' => $validated['cidade_origem'],
                'uf_origem' => strtoupper($validated['uf_origem']),
                'cidade_destino' => $validated['cidade_destino'],
                'uf_destino' => strtoupper($validated['uf_destino']),
                'distancia_km' => $validated['distancia_km'] ?? null,
                'valor_frete' => $validated['valor_frete'],
                'taxa_plataforma' => $taxaPlataforma,
                'data_coleta' => $validated['data_coleta'],
                'data_entrega_prevista' => $validated['data_entrega_prevista'] ?? null,
                'status' => 'publicada'
            ]);

            // 2. Gera a Assinatura Criptográfica do Termo de Publicação (Legalidade/Compliance)
            $termo = "TERMO DE PUBLICAÇÃO DE FRETE. O Embarcador ID {$user->embarcador->id} declara a veracidade dos dados da carga ID {$novaCarga->id}, com origem em {$novaCarga->cidade_origem}/{$novaCarga->uf_origem} e destino a {$novaCarga->cidade_destino}/{$novaCarga->uf_destino}, referente ao produto {$novaCarga->produto} ({$novaCarga->peso_kg}kg), oferecendo o valor de R$ " . number_format($novaCarga->valor_frete, 2, ',', '.') . " e concorda com a taxa de intermediação de R$ " . number_format($taxaPlataforma, 2, ',', '.') . ".";

            $hashTermo = hash('sha256', $termo);

            DB::table('carga_publicacoes_log')->insert([
                'carga_id' => $novaCarga->id,
                'embarcador_id' => $user->embarcador->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->header('User-Agent'), 0, 255),
                'termo_hash' => $hashTermo,
                'publicado_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $novaCarga;
        });

        // Retorna a Carga criada. O Observer nativo (que já criamos) disparará o WebSockets para os Motoristas.
        return response()->json([
            'message' => 'Carga publicada e certificada com sucesso.',
            'carga' => $carga
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if (!$user->role || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            return response()->json(['message' => 'Acesso restrito.'], 403);
        }

        $cargas = Carga::with(['motorista.user:id,name,email', 'publicacao_log', 'aceite_log', 'ciot'])
            ->where('embarcador_id', $user->embarcador->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($cargas);
    }

    public function show(Carga $carga, Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if ($user->role && $user->role->slug === 'embarcador' && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $carga->load(['embarcador', 'motorista.user', 'aceite_log', 'publicacao_log', 'ciot']);
        return response()->json($carga);
    }

    public function update(StoreCargaRequest $request, Carga $carga)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if ($user->role && $user->role->slug === 'embarcador' && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($carga->status !== 'publicada') {
            return response()->json(['message' => 'Cargas em negociação não podem ser editadas.'], 403);
        }

        $validated = $request->validated();
        $taxaPlataforma = round($validated['valor_frete'] * 0.05, 2);

        $carga->update([
            'produto' => $validated['produto'],
            'especie' => $validated['especie'],
            'peso_kg' => $validated['peso_kg'],
            'cubagem_m3' => $validated['cubagem_m3'] ?? null,
            'tipo_veiculo' => $validated['tipo_veiculo'],
            'tipo_carroceria' => $validated['tipo_carroceria'],
            'cidade_origem' => $validated['cidade_origem'],
            'uf_origem' => strtoupper($validated['uf_origem']),
            'cidade_destino' => $validated['cidade_destino'],
            'uf_destino' => strtoupper($validated['uf_destino']),
            'distancia_km' => $validated['distancia_km'] ?? null,
            'valor_frete' => $validated['valor_frete'],
            'taxa_plataforma' => $taxaPlataforma,
            'data_coleta' => $validated['data_coleta'],
            'data_entrega_prevista' => $validated['data_entrega_prevista'] ?? null,
        ]);

        return response()->json(['message' => 'Atualizada com sucesso.', 'carga' => $carga]);
    }

    public function destroy(Carga $carga, Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if ($user->role && $user->role->slug === 'embarcador' && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($carga->status !== 'publicada') {
            return response()->json(['message' => 'Status inválido para cancelamento.'], 403);
        }

        $carga->delete();
        return response()->json(['message' => 'Carga removida.']);
    }

    public function aprovarEntrega(Request $request, Carga $carga)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if ($user->role && $user->role->slug === 'embarcador' && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($carga->status !== 'em_auditoria') {
            return response()->json(['error' => 'Ação inválida.'], 400);
        }

        DB::transaction(function () use ($carga) {
            $ciot = Ciot::where('carga_id', $carga->id)->lockForUpdate()->first();
            
            if ($ciot && $ciot->status === 'emitido') {
                LiquidarFreteJob::dispatch($ciot->codigo_ciot)->onQueue('financeiro');
                $ciot->update(['status' => 'processando_liquidacao']);
            }

            $carga->update(['status' => 'entregue']);
        });

        return response()->json([
            'message' => 'Auditoria aprovada. Ordem de pagamento enviada para a fila de processamento.',
            'carga' => $carga
        ], 200);
    }
}