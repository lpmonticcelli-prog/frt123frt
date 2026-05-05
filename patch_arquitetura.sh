#!/bin/bash

echo "[123fretei] Iniciando deploy da Arquitetura Event-Driven para CIOT..."

# Garante que os diretórios existam
mkdir -p app/Jobs
mkdir -p app/Http/Controllers/Api/V1/Webhooks

# 1. MIGRATION: IDEMPOTÊNCIA PARA CIOTS
TIMESTAMP=$(date +"%Y_%m_%d_%H%M%S")
MIGRATION_FILE="database/migrations/${TIMESTAMP}_add_idempotency_to_ciots_table.php"
cat << 'EOF' > "$MIGRATION_FILE"
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ciots', function (Blueprint $table) {
            $table->uuid('idempotency_key')->unique()->after('id')->comment('Chave unica para evitar dupla emissao no Pamcard/NDD');
            $table->json('webhook_payload')->nullable()->after('pef_payload_response')->comment('Log completo do retorno assincrono');
        });
    }

    public function down(): void
    {
        Schema::table('ciots', function (Blueprint $table) {
            $table->dropColumn(['idempotency_key', 'webhook_payload']);
        });
    }
};
EOF
echo " -> Migration de Idempotência criada."

# 2. MODEL: ATUALIZANDO O CIOT.PHP
cat << 'EOF' > app/Models/Ciot.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ciot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'idempotency_key',
        'carga_id',
        'embarcador_id',
        'motorista_id',
        'codigo_ciot',
        'status',
        'valor_frete_bruto',
        'imposto_inss',
        'imposto_sest_senat',
        'imposto_irrf',
        'valor_vale_pedagio',
        'taxa_123fretei',
        'valor_frete_liquido',
        'pef_payload_response',
        'webhook_payload',
    ];

    protected $casts = [
        'valor_frete_bruto' => 'decimal:2',
        'imposto_inss' => 'decimal:2',
        'imposto_sest_senat' => 'decimal:2',
        'imposto_irrf' => 'decimal:2',
        'valor_vale_pedagio' => 'decimal:2',
        'taxa_123fretei' => 'decimal:2',
        'valor_frete_liquido' => 'decimal:2',
        'pef_payload_response' => 'array',
        'webhook_payload' => 'array',
    ];

    public function carga() { return $this->belongsTo(Carga::class); }
    public function embarcador() { return $this->belongsTo(Embarcador::class); }
    public function motorista() { return $this->belongsTo(Motorista::class); }
}
EOF
echo " -> Model Ciot.php atualizado."

# 3. JOB: EMISSÃO DE CIOT
cat << 'EOF' > app/Jobs/SolicitarEmissaoCiotJob.php
<?php

namespace App\Jobs;

use App\Models\Ciot;
use App\Contracts\PefGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SolicitarEmissaoCiotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Exponential backoff se a API externa falhar

    protected $ciotId;

    public function __construct(int $ciotId)
    {
        $this->ciotId = $ciotId;
    }

    public function handle(PefGatewayInterface $pefGateway): void
    {
        $ciot = Ciot::with('carga')->find($this->ciotId);

        if (!$ciot || $ciot->status !== 'processando') {
            Log::warning("[Worker] CIOT {$this->ciotId} não encontrado ou já processado.");
            return;
        }

        try {
            // A API deve aceitar a idempotency_key para não duplicar fretes
            $response = $pefGateway->emitirCiot($ciot->carga);

            $ciot->update([
                'codigo_ciot' => $response['codigo_ciot'],
                'pef_payload_response' => $response,
                'status' => 'aguardando_webhook' // Espera o callback oficial
            ]);

            Log::info("[Worker] Pedido de emissão do CIOT {$this->ciotId} enviado com sucesso.");
        } catch (\Exception $e) {
            Log::error("[Worker] Falha na emissão do CIOT: " . $e->getMessage());
            throw $e; // Força o retry na fila
        }
    }
}
EOF
echo " -> Job SolicitarEmissaoCiotJob.php criado."

# 4. JOB: LIQUIDAÇÃO DE FRETE (Pagamento Final)
cat << 'EOF' > app/Jobs/LiquidarFreteJob.php
<?php

namespace App\Jobs;

use App\Models\Ciot;
use App\Contracts\PefGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LiquidarFreteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [15, 45, 90]; 

    protected $codigoCiot;

    public function __construct(string $codigoCiot)
    {
        $this->codigoCiot = $codigoCiot;
    }

    public function handle(PefGatewayInterface $pefGateway): void
    {
        try {
            $sucesso = $pefGateway->liquidarFrete($this->codigoCiot);
            
            if ($sucesso) {
                Ciot::where('codigo_ciot', $this->codigoCiot)->update(['status' => 'liquidado']);
                Log::info("[Worker] CIOT {$this->codigoCiot} liquidado no banco emissor.");
            }
        } catch (\Exception $e) {
            Log::error("[Worker] Falha na liquidação do CIOT {$this->codigoCiot}: " . $e->getMessage());
            throw $e;
        }
    }
}
EOF
echo " -> Job LiquidarFreteJob.php criado."

# 5. CONTROLLER: WEBHOOK RECEIVER (Zero Trust Input Validation)
cat << 'EOF' > app/Http/Controllers/Api/V1/Webhooks/PefWebhookController.php
<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Ciot;
use App\Models\Carga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PefWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Zero Trust: Validação do Token de Segurança do Webhook
        $token = $request->header('X-PEF-Signature') ?? $request->query('token');
        if ($token !== config('services.pef.webhook_secret')) {
            Log::alert("[Segurança] Tentativa de injeção em Webhook PEF bloqueada. IP: " . $request->ip());
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $idempotencyKey = $payload['idempotency_key'] ?? null;
        $statusGateway = $payload['status'] ?? null;

        if (!$idempotencyKey) {
            return response()->json(['error' => 'Idempotency key is missing'], 400);
        }

        DB::transaction(function () use ($idempotencyKey, $statusGateway, $payload) {
            $ciot = Ciot::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if (!$ciot || $ciot->status === 'emitido') {
                return; // Já processado ou não existe
            }

            if ($statusGateway === 'EMITIDO_ANTT') {
                $ciot->update([
                    'status' => 'emitido',
                    'webhook_payload' => $payload
                ]);

                // Libera a carga para viagem na máquina de estados
                Carga::where('id', $ciot->carga_id)->update(['status' => 'em_viagem']);
                Log::info("[Webhook] CIOT {$ciot->codigo_ciot} consolidado na ANTT. Viagem liberada.");
            }
        });

        return response()->json(['received' => true]);
    }
}
EOF
echo " -> Controller Webhook criado."

# 6. CONTROLLER: CARGA CONTROLLER REESCRITO (Anti-Fragilidade)
cat << 'EOF' > app/Http/Controllers/Api/V1/Embarcador/CargaController.php
<?php

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ciot;
use App\Http\Requests\StoreCargaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessarPublicacaoCarga;
use App\Jobs\LiquidarFreteJob;

class CargaController extends Controller
{
    public function store(StoreCargaRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        ProcessarPublicacaoCarga::dispatch(
            $validated,
            $user->embarcador->id,
            $request->ip(),
            $request->header('User-Agent')
        )->onQueue('default');

        return response()->json([
            'message' => 'Lote recebido com sucesso. Sua carga está sendo processada.',
        ], 202);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role', 'embarcador');

        if (!$user->role || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            return response()->json(['message' => 'Acesso restrito.'], 403);
        }

        $cargas = Carga::with(['motorista.user:id,name,email', 'publicacao_log', 'aceite_log'])
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

    /**
     * NOVO FLUXO: Liquidação totalmente assíncrona.
     * O pool de banco de dados não será travado esperando o Gateway.
     */
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
                // DESPACHO PARA A FILA - Zero I/O de rede dentro da transação!
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
EOF
echo " -> CargaController.php sobrescrito com arquitetura assíncrona."

echo "[123fretei] Patch aplicado com sucesso."
