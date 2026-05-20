<?php

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ciot;
use App\Http\Requests\StoreCargaRequest;
use App\Jobs\LiquidarFreteJob;
use App\Services\Logistics\CandidaturaService;
use App\Services\Reputation\ReputacaoMotoristaService;
use App\Events\NovaMensagemChat; // 🔥 IMPORTAÇÃO DO EVENTO WEBSOCKET
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CargaController extends Controller
{
    protected CandidaturaService $candidaturaService;
    protected ReputacaoMotoristaService $reputacaoService;

    public function __construct(
        CandidaturaService $candidaturaService,
        ReputacaoMotoristaService $reputacaoService
    ) {
        $this->candidaturaService = $candidaturaService;
        $this->reputacaoService = $reputacaoService;
    }

    public function store(StoreCargaRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $carga = DB::transaction(function () use ($validated, $user, $request) {
            $percentualTaxa = $user->embarcador->taxa_frete_percentual ?? 5.00;
            $taxaPlataforma = round($validated['valor_frete'] * ($percentualTaxa / 100), 2);

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

            $termo = "TERMO DE PUBLICAÇÃO DE FRETE. O Embarcador ID {$user->embarcador->id} declara a veracidade dos dados da carga ID {$novaCarga->id}, com origem em {$novaCarga->cidade_origem}/{$novaCarga->uf_origem} e destino a {$novaCarga->cidade_destino}/{$novaCarga->uf_destino}, referente ao produto {$novaCarga->produto} ({$novaCarga->peso_kg}kg), oferecendo o valor de R$ " . number_format($novaCarga->valor_frete, 2, ',', '.') . " e concorda com a taxa de intermediação de R$ " . number_format($taxaPlataforma, 2, ',', '.') . " ({$percentualTaxa}%).";

            DB::table('carga_publicacoes_log')->insert([
                'carga_id' => $novaCarga->id,
                'embarcador_id' => $user->embarcador->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->header('User-Agent'), 0, 255),
                'termo_hash' => hash('sha256', $termo),
                'publicado_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $novaCarga;
        });

        return response()->json(['message' => 'Carga publicada.', 'carga' => $carga], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if (!$user->role || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            return response()->json(['message' => 'Acesso restrito.'], 403);
        }

        $cargas = Carga::with(['motorista.user:id,name,email', 'candidaturas', 'ciot'])
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

        $carga->load(['embarcador', 'motorista.user', 'ciot', 'candidaturas.motorista.user']);
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
        $percentualTaxa = $user->embarcador->taxa_frete_percentual ?? 5.00;
        $taxaPlataforma = round($validated['valor_frete'] * ($percentualTaxa / 100), 2);

        $carga->update(array_merge($validated, [
            'taxa_plataforma' => $taxaPlataforma,
            'uf_origem' => strtoupper($validated['uf_origem']),
            'uf_destino' => strtoupper($validated['uf_destino'])
        ]));

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
            return response()->json(['message' => 'Status inválido.'], 403);
        }

        $carga->delete();
        return response()->json(['message' => 'Carga removida.']);
    }

    public function aprovarCandidato(Request $request, Carga $carga)
    {
        $request->validate(['candidatura_id' => 'required|integer|exists:carga_candidaturas,id']);

        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            $this->candidaturaService->aprovarCandidato($carga->id, $request->candidatura_id, $user->embarcador->id);
            return response()->json(['message' => 'Motorista aprovado.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function avaliarEFinalizarEntrega(Request $request, Carga $carga)
    {
        $request->validate([
            'nota_pontualidade' => 'required|integer|min:1|max:5',
            'nota_cuidado'      => 'required|integer|min:1|max:5',
            'nota_comunicacao'  => 'required|integer|min:1|max:5',
            'houve_avaria'      => 'required|boolean',
            'comentarios'       => 'nullable|string|max:1000'
        ]);

        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($carga->status !== 'em_auditoria') {
            return response()->json(['error' => 'A carga não está aguardando auditoria.'], 400);
        }

        try {
            $avaliacao = $this->reputacaoService->processarAvaliacao(
                $carga->id, $user->embarcador->id, $carga->motorista_id,
                $request->nota_pontualidade, $request->nota_cuidado,
                $request->nota_comunicacao, $request->houve_avaria, $request->comentarios
            );

            DB::transaction(function () use ($carga, $request) {
                if ($request->houve_avaria) {
                    $carga->update(['status' => 'em_disputa']);
                    Ciot::where('carga_id', $carga->id)->lockForUpdate()->update(['status' => 'bloqueado_disputa']);
                } else {
                    $carga->update(['status' => 'entregue']);
                    $ciot = Ciot::where('carga_id', $carga->id)->lockForUpdate()->first();
                    if ($ciot && $ciot->status === 'emitido') {
                        LiquidarFreteJob::dispatch($ciot->codigo_ciot)->onQueue('financeiro');
                        $ciot->update(['status' => 'processando_liquidacao']);
                    }
                }
            });

            $mensagem = $request->houve_avaria ? 'Avaria registrada e pagamento retido.' : 'Ordem de pagamento liberada.';
            return response()->json(['message' => $mensagem, 'carga' => $carga->fresh(['avaliacao'])], 200);

        } catch (\Exception $e) {
            Log::error("[Avaliação] Erro: " . $e->getMessage());
            return response()->json(['error' => 'Falha ao processar avaliação.'], 500);
        }
    }

    public function abrirDisputa(Request $request, Carga $carga)
    {
        $request->validate(['motivo' => 'required|string|max:1000']);
        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) { return response()->json(['message' => 'Acesso negado.'], 403); }

        DB::transaction(function () use ($carga) {
            $carga->update(['status' => 'em_disputa']);
            Ciot::where('carga_id', $carga->id)->lockForUpdate()->update(['status' => 'bloqueado_disputa']);
        });

        return response()->json(['message' => 'Disputa aberta com sucesso.'], 200);
    }

    // =====================================================================
    // CHAT OPERACIONAL - ZERO TRUST COMPLIANCE COM WEBSOCKET
    // =====================================================================
    public function getChat(Request $request, Carga $carga)
    {
        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $mensagens = DB::table('carga_mensagens')
            ->where('carga_id', $carga->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($mensagens);
    }

    public function storeChat(Request $request, Carga $carga)
    {
        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate(['mensagem' => 'required|string|max:1000']);
        $mensagemLimpa = $request->mensagem;

        // MOTOR DE NLP / REGEX (Bloqueia Contatos)
        $patternDDI = '/\+?\d{2,3}[\s-]?\d{2}[\s-]?\d{4,5}[\s-]?\d{4}/'; 
        $patternTel = '/\b(?:\(?\d{2}\)?\s?)?(?:9\d{4}|\d{4})[-\s]?\d{4}\b/'; 
        $patternSocial = '/\b(whatsapp|whats|wpp|telegram|facebook|insta|instagram|tiktok|skype|linkedin|twiter|x\.com)\b/i';
        $patternEmail = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        $patternObfus = '/(?:zero|um|dois|três|quatro|cinco|seis|sete|oito|nove).*?(?:zero|um|dois)/i'; 

        if (
            preg_match($patternTel, $mensagemLimpa) || 
            preg_match($patternSocial, $mensagemLimpa) || 
            preg_match($patternEmail, $mensagemLimpa) ||
            preg_match($patternDDI, $mensagemLimpa) ||
            preg_match($patternObfus, $mensagemLimpa)
        ) {
            return response()->json([
                'error' => '⛔ ALERTA DE COMPLIANCE: É proibido enviar telefone, e-mail ou redes sociais. O chat é exclusivo para assuntos da carga. Reincidências geram bloqueio automático da conta.'
            ], 403);
        }

        $id = DB::table('carga_mensagens')->insertGetId([
            'carga_id' => $carga->id,
            'remetente_id' => $user->embarcador->id,
            'remetente_tipo' => 'embarcador',
            'mensagem' => $mensagemLimpa,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $msg = DB::table('carga_mensagens')->find($id);

        // 🔥 O ALTO-FALANTE: Avisa o Motorista no canal do WebSocket que há uma nova mensagem
        broadcast(new NovaMensagemChat($msg, $carga->id))->toOthers();

        return response()->json($msg, 201);
    }
}