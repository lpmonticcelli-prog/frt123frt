<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ciot;
use App\Http\Requests\StoreCargaRequest;
use App\Jobs\LiquidarFreteJob;
use App\Services\Logistics\CandidaturaService;
use App\Services\Reputation\ReputacaoMotoristaService;
use App\Events\NovaMensagemChat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Sanitização Termal Estrita (Defesa contra XSS/Null Byte em formulários e chat).
     */
    private function sanitizeText(?string $payload): ?string
    {
        if ($payload === null) {
            return null;
        }
        $clean = str_replace(chr(0), '', $payload);
        $clean = strip_tags($clean);
        return htmlspecialchars($clean, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', false);
    }

    public function store(StoreCargaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $carga = DB::transaction(function () use ($validated, $user, $request) {
            $percentualTaxa = $user->embarcador->taxa_frete_percentual ?? 5.00;
            $taxaPlataforma = round($validated['valor_frete'] * ($percentualTaxa / 100), 2);

            $novaCarga = Carga::create([
                'embarcador_id' => $user->embarcador->id,
                'produto' => $this->sanitizeText($validated['produto']),
                'especie' => $this->sanitizeText($validated['especie']),
                'peso_kg' => $validated['peso_kg'],
                'cubagem_m3' => $validated['cubagem_m3'] ?? null,
                'tipo_veiculo' => $validated['tipo_veiculo'],
                'tipo_carroceria' => $validated['tipo_carroceria'],
                'cidade_origem' => $this->sanitizeText($validated['cidade_origem']),
                'uf_origem' => strtoupper($validated['uf_origem']),
                'cidade_destino' => $this->sanitizeText($validated['cidade_destino']),
                'uf_destino' => strtoupper($validated['uf_destino']),
                'distancia_km' => $validated['distancia_km'] ?? null,
                'valor_frete' => $validated['valor_frete'],
                'taxa_plataforma' => $taxaPlataforma, 
                'data_coleta' => $validated['data_coleta'],
                'data_entrega_prevista' => $validated['data_entrega_prevista'] ?? null,
                'status' => 'publicada'
            ]);

            $termo = "TERMO DE PUBLICAÇÃO DE FRETE. O Embarcador ID {$user->embarcador->id} declara a veracidade dos dados da carga ID {$novaCarga->id}, com origem em {$novaCarga->cidade_origem}/{$novaCarga->uf_origem} e destino a {$novaCarga->cidade_destino}/{$novaCarga->uf_destino}, referente ao produto {$novaCarga->produto} ({$novaCarga->peso_kg}kg), oferecendo o valor de R$ " . number_format((float)$novaCarga->valor_frete, 2, ',', '.') . " e concorda com a taxa de intermediação de R$ " . number_format($taxaPlataforma, 2, ',', '.') . " ({$percentualTaxa}%).";

            DB::table('carga_publicacoes_log')->insert([
                'carga_id' => $novaCarga->id,
                'embarcador_id' => $user->embarcador->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->header('User-Agent'), 0, 255),
                'termo_hash' => hash('sha256', $termo),
                'publicado_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $novaCarga;
        });

        return response()->json(['message' => 'Carga publicada.', 'carga' => $carga], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if (!$user->role || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            return response()->json(['message' => 'Acesso restrito.'], 403);
        }

        $cargas = Carga::with([
            'motorista.user', // O Gêmeo Digital (C3) necessita dos dados completos do motorista ATRIBUÍDO
            'ciot',
            'candidaturas',
            // ZERO TRUST PROJECTION: Oculta estritamente PII e Contatos (Mass Data Breach Fix) de candidatos no leilão
            'candidaturas.motorista' => function ($query) {
                $query->select('id', 'user_id', 'score_geral', 'total_viagens', 'tier_reputacao');
            },
            'candidaturas.motorista.user:id,name' // Decapita email e telefone da query
        ])
            ->where('embarcador_id', $user->embarcador->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($cargas);
    }

    public function show(Carga $carga, Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if ($user->role && $user->role->slug === 'embarcador' && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $carga->load([
            'embarcador', 
            'motorista.user', 
            'ciot', 
            'candidaturas',
            // ZERO TRUST PROJECTION: Evita serialização gulosa do Eloquent ORM
            'candidaturas.motorista' => function ($query) {
                $query->select('id', 'user_id', 'score_geral', 'total_viagens', 'tier_reputacao');
            },
            'candidaturas.motorista.user:id,name'
        ]);
        
        return response()->json($carga);
    }

    public function update(StoreCargaRequest $request, Carga $carga): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if ($user->role && $user->role->slug === 'embarcador' && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $validated = $request->validated();
        $percentualTaxa = $user->embarcador->taxa_frete_percentual ?? 5.00;
        $taxaPlataforma = round($validated['valor_frete'] * ($percentualTaxa / 100), 2);

        DB::transaction(function () use ($carga, $validated, $taxaPlataforma) {
            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            if ($cargaLock->status !== 'publicada') {
                abort(403, 'Cargas em processamento não podem ser editadas.');
            }

            // ZT-DEFENSE: Anti-Bait-and-Switch
            if ($cargaLock->candidaturas()->where('status', 'pendente')->exists()) {
                abort(409, 'Fraude evitada: Não é permitido alterar o valor ou rota de uma carga que já possui lances ativos de motoristas. Cancele a carga ou rejeite os lances primeiro.');
            }

            $cargaLock->update(array_merge($validated, [
                'produto' => $this->sanitizeText($validated['produto']),
                'especie' => $this->sanitizeText($validated['especie']),
                'cidade_origem' => $this->sanitizeText($validated['cidade_origem']),
                'cidade_destino' => $this->sanitizeText($validated['cidade_destino']),
                'taxa_plataforma' => $taxaPlataforma,
                'uf_origem' => strtoupper($validated['uf_origem']),
                'uf_destino' => strtoupper($validated['uf_destino'])
            ]));
        });

        return response()->json(['message' => 'Atualizada com sucesso.', 'carga' => $carga->fresh()]);
    }

    public function destroy(Carga $carga, Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if ($user->role && $user->role->slug === 'embarcador' && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        DB::transaction(function () use ($carga) {
            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            if ($cargaLock->status !== 'publicada') {
                abort(403, 'Status inválido. Apenas cargas publicadas podem ser excluídas.');
            }

            $cargaLock->delete();
        });

        return response()->json(['message' => 'Carga removida.']);
    }

    public function aprovarCandidato(Request $request, Carga $carga): JsonResponse
    {
        $request->validate(['candidatura_id' => 'required|integer|exists:carga_candidaturas,id']);

        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            $this->candidaturaService->aprovarCandidato($carga->id, (int) $request->candidatura_id, $user->embarcador->id);
            return response()->json(['message' => 'Motorista aprovado.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function avaliarEFinalizarEntrega(Request $request, Carga $carga): JsonResponse
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
                (int) $request->nota_pontualidade, (int) $request->nota_cuidado,
                (int) $request->nota_comunicacao, (bool) $request->houve_avaria, 
                $this->sanitizeText($request->comentarios)
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

    public function abrirDisputa(Request $request, Carga $carga): JsonResponse
    {
        $request->validate(['motivo' => 'required|string|max:1000']);
        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) { 
            return response()->json(['message' => 'Acesso negado.'], 403); 
        }

        DB::transaction(function () use ($carga) {
            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            // ZT-DEFENSE: State Machine Validation (Disputa Zumbi)
            $estadosAuditiveis = ['em_transito', 'em_auditoria', 'entregue'];
            if (!in_array($cargaLock->status, $estadosAuditiveis, true)) {
                abort(400, "Operação inválida. O status atual ('{$cargaLock->status}') não permite a abertura de disputas financeiras e operacionais.");
            }

            $cargaLock->update(['status' => 'em_disputa']);
            Ciot::where('carga_id', $cargaLock->id)->lockForUpdate()->update(['status' => 'bloqueado_disputa']);
        });

        return response()->json(['message' => 'Disputa aberta com sucesso.'], 200);
    }

    // =====================================================================
    // ZT-DEFENSE: Proxy Restrito de Arquivos (POD View)
    // =====================================================================
    public function exibirDocumentoPod(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            abort(403, 'Acesso negado.');
        }

        $path = $request->query('path');

        if (!$path || preg_match('/\.{2}/', $path) || str_contains($path, '../') || str_contains($path, '..\\')) {
            Log::alert("Security Audit: Tentativa de LFI/Path Traversal em POD. IP: " . $request->ip());
            return response()->json(['error' => 'Violação de perímetro detectada.'], 403);
        }

        if (!str_starts_with($path, 'pod/')) {
            return response()->json(['error' => 'Acesso a diretório não autorizado.'], 403);
        }

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'Documento de entrega não encontrado no cofre.'], 404);
        }

        // Recupera o ID da carga através do nome da pasta para checar titularidade. ex: pod/carga_123/foto.jpg
        preg_match('/carga_(\d+)/', $path, $matches);
        if (isset($matches[1])) {
            $cargaId = (int) $matches[1];
            $carga = Carga::select('embarcador_id')->find($cargaId);
            
            if (!$carga || $carga->embarcador_id !== $user->embarcador->id) {
                return response()->json(['error' => 'Você não é o contratante deste frete. Acesso bloqueado.'], 403);
            }
        } else {
             return response()->json(['error' => 'Caminho de documento inválido ou corrompido.'], 400);
        }

        return Storage::disk('local')->response($path);
    }

    // =====================================================================
    // CHAT OPERACIONAL - ZERO TRUST COMPLIANCE COM WEBSOCKET
    // =====================================================================
    public function getChat(Request $request, Carga $carga): JsonResponse
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

    public function storeChat(Request $request, Carga $carga): JsonResponse
    {
        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate(['mensagem' => 'required|string|max:1000']);
        
        // ZT-DEFENSE: Sanitização térmica contra XSS/Null bytes injetados via Socket
        $mensagemLimpa = $this->sanitizeText($request->mensagem);

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

        broadcast(new NovaMensagemChat($msg, $carga->id))->toOthers();

        return response()->json($msg, 201);
    }
}