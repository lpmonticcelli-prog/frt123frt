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
use Throwable;

class CargaController extends Controller
{
    private const ROLE_EMBARCADOR = 'embarcador';
    private const STATUS_PUBLICADA = 'publicada';
    private const STATUS_EM_AUDITORIA = 'em_auditoria';
    private const STATUS_EM_DISPUTA = 'em_disputa';
    private const STATUS_ENTREGUE = 'entregue';
    
    // Status de bloqueio
    private const CIOT_BLOQUEADO_DISPUTA = 'bloqueado_disputa';
    private const CIOT_PROCESSANDO_LIQUIDACAO = 'processando_liquidacao';

    public function __construct(
        private readonly CandidaturaService $candidaturaService,
        private readonly ReputacaoMotoristaService $reputacaoService
    ) {}

    /**
     * Sanitização Termal Estrita (Defesa contra XSS/Null Byte).
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

        try {
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
                    'status' => self::STATUS_PUBLICADA
                ]);

                $termo = sprintf(
                    "TERMO DE PUBLICAÇÃO DE FRETE. O Embarcador ID %d declara a veracidade dos dados da carga ID %d, com origem em %s/%s e destino a %s/%s, referente ao produto %s (%skg), oferecendo o valor de R$ %s e concorda com a taxa de intermediação de R$ %s (%s%%).",
                    $user->embarcador->id,
                    $novaCarga->id,
                    $novaCarga->cidade_origem,
                    $novaCarga->uf_origem,
                    $novaCarga->cidade_destino,
                    $novaCarga->uf_destino,
                    $novaCarga->produto,
                    $novaCarga->peso_kg,
                    number_format((float)$novaCarga->valor_frete, 2, ',', '.'),
                    number_format($taxaPlataforma, 2, ',', '.'),
                    $percentualTaxa
                );

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

            return response()->json(['message' => 'Carga publicada com sucesso.', 'carga' => $carga], 201);
            
        } catch (Throwable $e) {
            Log::critical('Falha atômica ao publicar carga', [
                'embarcador_id' => $user->embarcador->id ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Falha interna ao processar a publicação.'], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if (!$user->role || $user->role->slug !== self::ROLE_EMBARCADOR || !$user->embarcador) {
            return response()->json(['message' => 'Acesso restrito.'], 403);
        }

        $cargas = Carga::with([
            'motorista.user',
            'ciot',
            'candidaturas',
            'candidaturas.motorista' => function ($query) {
                $query->select('id', 'user_id', 'score_geral', 'total_viagens', 'tier_reputacao');
            },
            'candidaturas.motorista.user:id,name'
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

        if ($user->role && $user->role->slug === self::ROLE_EMBARCADOR && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado. Esta carga não pertence à sua organização.'], 403);
        }

        $carga->load([
            'embarcador', 
            'motorista.user', 
            'ciot', 
            'candidaturas',
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

        if ($user->role && $user->role->slug === self::ROLE_EMBARCADOR && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $validated = $request->validated();
        
        try {
            DB::beginTransaction();
            
            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            if ($cargaLock->status !== self::STATUS_PUBLICADA) {
                abort(403, 'Cargas em processamento (' . $cargaLock->status . ') não podem ser editadas.');
            }

            // Anti-Bait-and-Switch
            if ($cargaLock->candidaturas()->where('status', 'pendente')->exists()) {
                abort(409, 'Fraude evitada: Não é permitido alterar o valor ou rota de uma carga com lances ativos. Cancele a carga ou rejeite os lances.');
            }

            $percentualTaxa = $user->embarcador->taxa_frete_percentual ?? 5.00;
            $taxaPlataforma = round($validated['valor_frete'] * ($percentualTaxa / 100), 2);

            $cargaLock->update(array_merge($validated, [
                'produto' => $this->sanitizeText($validated['produto']),
                'especie' => $this->sanitizeText($validated['especie']),
                'cidade_origem' => $this->sanitizeText($validated['cidade_origem']),
                'cidade_destino' => $this->sanitizeText($validated['cidade_destino']),
                'taxa_plataforma' => $taxaPlataforma,
                'uf_origem' => strtoupper($validated['uf_origem']),
                'uf_destino' => strtoupper($validated['uf_destino'])
            ]));
            
            DB::commit();
            return response()->json(['message' => 'Carga atualizada com sucesso.', 'carga' => $carga->fresh()]);
            
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            DB::rollBack();
            Log::critical('Erro atômico na atualização da carga', ['carga_id' => $carga->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha interna ao atualizar carga.'], 500);
        }
    }

    public function destroy(Carga $carga, Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if ($user->role && $user->role->slug === self::ROLE_EMBARCADOR && $carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            DB::transaction(function () use ($carga) {
                $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

                if ($cargaLock->status !== self::STATUS_PUBLICADA) {
                    abort(403, 'Apenas cargas publicadas podem ser excluídas.');
                }

                $cargaLock->delete();
            });

            return response()->json(['message' => 'Carga removida do sistema.']);
            
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            Log::error('Falha ao deletar carga', ['carga_id' => $carga->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno ao remover carga.'], 500);
        }
    }

    public function aprovarCandidato(Request $request, Carga $carga): JsonResponse
    {
        $request->validate(['candidatura_id' => 'required|integer|exists:carga_candidaturas,id']);

        $user = $request->user();
        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            // Delegação atômica para a camada de serviço (que já deve possuir sua própria transação)
            $this->candidaturaService->aprovarCandidato($carga->id, (int) $request->candidatura_id, $user->embarcador->id);
            return response()->json(['message' => 'Motorista aprovado. O processo de alocação foi iniciado.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Auditoria Financeira Crítica: Finalização e Liberação de Ciot.
     */
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

        if ($carga->status !== self::STATUS_EM_AUDITORIA) {
            return response()->json(['error' => 'A carga não está aguardando auditoria ou já foi liquidada.'], 400);
        }

        try {
            DB::beginTransaction();
            
            // Processa a avaliação (deve ser idempotente ou fazer parte da transação se possível)
            $avaliacao = $this->reputacaoService->processarAvaliacao(
                $carga->id, $user->embarcador->id, $carga->motorista_id,
                (int) $request->nota_pontualidade, (int) $request->nota_cuidado,
                (int) $request->nota_comunicacao, (bool) $request->houve_avaria, 
                $this->sanitizeText($request->comentarios)
            );

            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            if ($request->houve_avaria) {
                $cargaLock->update(['status' => self::STATUS_EM_DISPUTA]);
                Ciot::where('carga_id', $cargaLock->id)->lockForUpdate()->update(['status' => self::CIOT_BLOQUEADO_DISPUTA]);
            } else {
                $cargaLock->update(['status' => self::STATUS_ENTREGUE]);
                $ciot = Ciot::where('carga_id', $cargaLock->id)->lockForUpdate()->first();
                
                if ($ciot && $ciot->status === 'emitido') {
                    $ciot->update(['status' => self::CIOT_PROCESSANDO_LIQUIDACAO]);
                    // Job despachado apenas se o commit de banco for bem sucedido (ver listener no framework ou afterCommit)
                    LiquidarFreteJob::dispatch($ciot->codigo_ciot)->onQueue('financeiro')->afterCommit();
                }
            }

            DB::commit();

            $mensagem = $request->houve_avaria ? 'Avaria registrada. Pagamento bloqueado para disputa.' : 'Ordem de pagamento e liquidação do frete liberada com sucesso.';
            return response()->json(['message' => $mensagem, 'carga' => $cargaLock->fresh(['avaliacao'])], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::critical('Falha catastrófica ao liquidar frete', [
                'carga_id' => $carga->id, 
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Falha crítica ao processar a liquidação financeira.'], 500);
        }
    }

    public function abrirDisputa(Request $request, Carga $carga): JsonResponse
    {
        $request->validate(['motivo' => 'required|string|max:1000']);
        $user = $request->user();
        
        if ($carga->embarcador_id !== $user->embarcador->id) { 
            return response()->json(['message' => 'Acesso negado.'], 403); 
        }

        try {
            DB::transaction(function () use ($carga) {
                $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

                $estadosAuditiveis = ['em_transito', self::STATUS_EM_AUDITORIA, self::STATUS_ENTREGUE];
                if (!in_array($cargaLock->status, $estadosAuditiveis, true)) {
                    abort(400, "O status atual ('{$cargaLock->status}') impede a abertura de disputas financeiras.");
                }

                $cargaLock->update(['status' => self::STATUS_EM_DISPUTA]);
                Ciot::where('carga_id', $cargaLock->id)->lockForUpdate()->update(['status' => self::CIOT_BLOQUEADO_DISPUTA]);
            });

            return response()->json(['message' => 'Fundos congelados. Disputa aberta com sucesso e notificada aos administradores.'], 200);
            
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            Log::error('Erro ao abrir disputa', ['carga_id' => $carga->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno ao processar a disputa.'], 500);
        }
    }

    /**
     * Proxy Zero Trust para visualização de POD (Proof of Delivery).
     * Saneado e agnóstico de ambiente (S3 / Local).
     */
    public function exibirDocumentoPod(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->role->slug !== self::ROLE_EMBARCADOR || !$user->embarcador) {
            return response()->json(['error' => 'Acesso restrito.'], 403);
        }

        $path = $request->query('path');

        if (!$path || preg_match('/\.{2}/', $path) || str_contains($path, '../') || str_contains($path, '..\\')) {
            Log::alert("Security Audit: Tentativa LFI/Path Traversal em POD detectada e contida.", ['ip' => $request->ip()]);
            return response()->json(['error' => 'Violação de perímetro.'], 403);
        }

        if (!str_starts_with($path, 'pod/')) {
            return response()->json(['error' => 'Escopo de diretório violado.'], 403);
        }

        // Recupera o ID da carga através da regex
        preg_match('/carga_(\d+)/', $path, $matches);
        if (!isset($matches[1])) {
             return response()->json(['error' => 'Assinatura do documento inválida.'], 400);
        }

        $cargaId = (int) $matches[1];
        $carga = Carga::select('embarcador_id')->find($cargaId);
        
        if (!$carga || $carga->embarcador_id !== $user->embarcador->id) {
            Log::warning('Tentativa de acesso IDOR a POD.', ['user_id' => $user->id, 'carga_id' => $cargaId]);
            return response()->json(['error' => 'Acesso negado. Documento pertence a outra organização.'], 403);
        }

        // Operação de I/O Abstrata (Storage Driver configurado)
        if (!Storage::exists($path)) {
            return response()->json(['error' => 'Objeto de entrega não encontrado na storage.'], 404);
        }

        return Storage::response($path);
    }

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
        
        $mensagemLimpa = $this->sanitizeText($request->mensagem);

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
            Log::info('Vazamento de PII interceptado no chat (Embarcador)', ['embarcador_id' => $user->embarcador->id, 'carga_id' => $carga->id]);
            return response()->json([
                'error' => '⛔ ALERTA: É estritamente proibido enviar dados de contato externo. A comunicação deve ocorrer na plataforma.'
            ], 403);
        }

        try {
            $id = DB::table('carga_mensagens')->insertGetId([
                'carga_id' => $carga->id,
                'remetente_id' => $user->embarcador->id,
                'remetente_tipo' => self::ROLE_EMBARCADOR,
                'mensagem' => $mensagemLimpa,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $msg = DB::table('carga_mensagens')->find($id);

            broadcast(new NovaMensagemChat($msg, $carga->id))->toOthers();

            return response()->json($msg, 201);
            
        } catch (Throwable $e) {
            Log::error('Erro ao despachar mensagem via Socket', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha interna ao processar a mensagem.'], 500);
        }
    }
}