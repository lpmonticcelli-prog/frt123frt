<?php

declare(strict_types=1);

namespace App\Services\Pef;

use App\Contracts\PefGatewayInterface;
use App\Models\Carga;
use App\DTOs\CiotResponseDTO;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MockPefGateway implements PefGatewayInterface
{
    /**
     * DTO Imutável: Garante a consistência do contrato com a integradora PEF.
     */
    public function emitirCiot(Carga $carga): CiotResponseDTO
    {
        Log::info("[MOCK PEF Gateway] Simulando emissão de CIOT para Carga ID {$carga->id}");

        $freteBruto = (float) $carga->valor_frete;
        
        $inss = round($freteBruto * 0.11, 2);
        $sestSenat = round($freteBruto * 0.015, 2);
        $irrf = 0.00; 
        $valePedagio = 150.00; 
        $taxaPlataforma = round($freteBruto * 0.05, 2); 
        
        $liquido = $freteBruto - $inss - $sestSenat - $irrf - $taxaPlataforma + $valePedagio;

        return new CiotResponseDTO(
            sucesso: true,
            codigoCiot: 'CIOT-' . strtoupper(Str::random(12)),
            bruto: $freteBruto,
            inss: $inss,
            sestSenat: $sestSenat,
            irrf: $irrf,
            valePedagio: $valePedagio,
            taxa123fretei: $taxaPlataforma,
            liquidoMotorista: $liquido,
            payloadOriginal: [
                'status' => 'approved', 
                'network' => 'mock_payment_network', 
                'timestamp' => now()->toIso8601String()
            ]
        );
    }

    public function cancelarCiot(string $codigoCiot): bool
    {
        Log::warning("[MOCK PEF Gateway] Efetuando rollback e cancelamento do CIOT: {$codigoCiot}");
        return true;
    }

    public function liquidarFrete(string $codigoCiot): bool
    {
        Log::info("[MOCK PEF Gateway] Ordem de saque Escrow emitida com sucesso. CIOT: {$codigoCiot}");
        return true;
    }
}