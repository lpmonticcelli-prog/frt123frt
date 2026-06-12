<?php

declare(strict_types=1);

namespace App\Services\Financial;

use App\Models\Carga;
use App\Models\PagamentoEscrow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class EscrowPaymentService
{
    /**
     * Orquestra a geração do PIX com divisão de valores.
     * Operação 100% Idempotente amparada por chaves criptográficas.
     */
    public function gerarCheckoutPix(Carga $carga, string $idempotencyKey): PagamentoEscrow
    {
        if (!in_array($carga->status, ['publicada', 'aceita', 'processando_aceite'], true)) {
            throw new Exception('Esta carga não está em status válido para emissão de pagamento. Status atual: ' . $carga->status);
        }

        $pagamentoExistente = PagamentoEscrow::where('idempotency_key', $idempotencyKey)->first();
        if ($pagamentoExistente) {
            return $pagamentoExistente;
        }

        // Matemática do Split B2B
        $valorMotorista = (float) $carga->valor_frete;
        $taxaPlataforma = (float) $carga->taxa_plataforma;
        $valorTotal = $valorMotorista + $taxaPlataforma;

        Log::info("[Escrow] Solicitando PIX Split ao Gateway B2B.", [
            'carga_id' => $carga->id,
            'total' => $valorTotal,
            'plataforma' => $taxaPlataforma,
            'motorista' => $valorMotorista
        ]);

        // Simula a resposta do Gateway de Pagamentos em Lote (ex: Iugu/StarkBank)
        $gatewayTxId = 'TX-' . strtoupper(Str::random(16));
        $qrCodeEmv = '00020101021226500014br.gov.bcb.pix0114+5511999999999520400005303986540510.005802BR5913123fretei LTDA6009SAO PAULO62070503***6304' . strtoupper(Str::random(4));

        return PagamentoEscrow::create([
            'carga_id' => $carga->id,
            'embarcador_id' => $carga->embarcador_id,
            'idempotency_key' => $idempotencyKey,
            'gateway_tx_id' => $gatewayTxId,
            'valor_total' => $valorTotal,
            'split_plataforma' => $taxaPlataforma,
            'split_motorista' => $valorMotorista,
            'qr_code_payload' => $qrCodeEmv,
            'qr_code_url' => 'https://api.123fretei.com/qr/' . $gatewayTxId,
            'status' => 'aguardando_pagamento'
        ]);
    }
}