<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Services\Financial\EscrowPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Exception;

class CheckoutController extends Controller
{
    public function __construct(private readonly EscrowPaymentService $escrowService) {}

    public function gerarPagamento(Request $request, Carga $carga): JsonResponse
    {
        $user = $request->user();

        // ==========================================
        // ZT-DEFENSE: SUDO MODE (Airgap Financeiro)
        // ==========================================
        // Mitigação final contra ataques de SOCKS5 Proxy/VNC. Se o vírus controlar a máquina do usuário
        // e enviar a requisição, ele vai bater nesta parede. O atacante não sabe a senha de cabeça. 
        // A transação é bloqueada antes de tocar no Banco.
        $request->validate([
            'password_confirmation' => 'required|string'
        ], [
            'password_confirmation.required' => 'Acesso Protegido: A sua senha é obrigatória para confirmar transações financeiras.'
        ]);

        if (!Hash::check($request->password_confirmation, $user->password)) {
            Log::alert("[SUDO MODE BLOCK] Tentativa de bypass financeiro. Acesso de malware ou invasor bloqueado.", ['user_id' => $user->id, 'ip' => $request->ip()]);
            return response()->json(['error' => 'Acesso negado. A assinatura criptográfica (senha) informada é inválida.'], 403);
        }

        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key');

        try {
            $pagamento = DB::transaction(function () use ($carga, $idempotencyKey) {
                $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();
                
                $pagamentoCriado = $this->escrowService->gerarCheckoutPix($cargaLock, $idempotencyKey);
                
                if ($cargaLock->status !== 'aguardando_pagamento') {
                    $cargaLock->update(['status' => 'aguardando_pagamento']);
                }

                return $pagamentoCriado;
            });

            return response()->json([
                'message' => 'Ordem Financeira B2B autorizada e gerada com sucesso.',
                'data' => [
                    'idempotency_key' => $pagamento->idempotency_key,
                    'gateway_tx_id' => $pagamento->gateway_tx_id,
                    'valor_total' => $pagamento->valor_total,
                    'qr_code_payload' => $pagamento->qr_code_payload,
                    'qr_code_url' => $pagamento->qr_code_url,
                    'status' => $pagamento->status,
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error("[Checkout] Falha ao gerar Escrow: " . $e->getMessage(), ['carga_id' => $carga->id]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}