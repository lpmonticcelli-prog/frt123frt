<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Motorista;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class IzaWebhookController extends Controller
{
    /**
     * Recebe e processa os eventos passivos enviados pela IZA Seguradora.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. ZT-DEFENSE: Validação do Segredo do Webhook (Header Customizado)
        // O token de segurança fornecido no painel da IZA
        $signature = $request->header('X-Iza-Signature') ?? $request->header('Authorization');
        $expectedSignature = config('services.iza.webhook_secret', env('IZA_WEBHOOK_SECRET'));

        if ($signature !== $expectedSignature) {
            Log::warning('[IZA_WEBHOOK] Tentativa de acesso não autorizada ao endpoint de seguro.', [
                'ip'                 => $request->ip(),
                'signature_recebida' => $signature
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2. Extração dos dados do payload
        $payload = $request->all();
        $policyId = $payload['policy_id'] ?? null;
        $statusIza = $payload['status'] ?? null; // Ex: 'active', 'cancelled', 'expired'

        Log::info('[IZA_WEBHOOK] Evento recebido da Seguradora.', $payload);

        if (!$policyId || !$statusIza) {
            return response()->json(['error' => 'Payload inválido: policy_id ou status ausente.'], 422);
        }

        // 3. Busca o Motorista atrelado a esta apólice (Lendo as colunas nativas novas)
        $motorista = Motorista::where('seguro_iza_id', $policyId)->first();

        if (!$motorista) {
            Log::error('[IZA_WEBHOOK] Apólice não encontrada no banco de dados da 123FRETEI.', ['policy_id' => $policyId]);
            return response()->json(['error' => 'Apólice não localizada no sistema.'], 404);
        }

        // 4. Mapeamento de status da IZA para o sistema 123FRETEI
        $novoStatus = $this->mapearStatus($statusIza);

        // 5. Atualização direta do status no cadastro do motorista
        $updateData = [
            'seguro_iza_status' => $novoStatus,
        ];

        // Atualiza a data de vencimento apenas se a IZA enviar uma nova data no payload
        if (isset($payload['end_date'])) {
            $updateData['seguro_iza_vencimento'] = $payload['end_date'];
        }

        $motorista->update($updateData);

        Log::info('[IZA_WEBHOOK] Apólice do motorista sincronizada com sucesso.', [
            'motorista_id' => $motorista->id,
            'policy_id'    => $policyId,
            'novo_status'  => $novoStatus
        ]);

        return response()->json(['message' => 'Webhook processado com sucesso.']);
    }

    /**
     * Padroniza os status recebidos da Iza para os status internos da plataforma.
     */
    private function mapearStatus(string $statusIza): string
    {
        return match (strtolower($statusIza)) {
            'active', 'emitted'     => 'ativo',
            'cancelled', 'expired'  => 'inativo', // Aciona o bloqueio novamente no frontend
            'suspended', 'past_due' => 'inadimplente',
            default                 => 'inativo',
        };
    }
}