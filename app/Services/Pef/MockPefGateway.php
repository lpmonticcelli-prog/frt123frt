<?php

namespace App\Services\Pef;

use App\Contracts\PefGatewayInterface;
use App\Models\Carga;
use App\DTOs\CiotResponseDTO;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MockPefGateway implements PefGatewayInterface
{
    public function emitirCiot(Carga $carga): CiotResponseDTO
    {
        Log::info("[MOCK PEF] Simulando emissão de CIOT para a carga {$carga->id}");

        $freteBruto = (float) $carga->valor_frete;
        
        // Simulação de matemática tributária
        $inss = round($freteBruto * 0.11, 2);
        $sestSenat = round($freteBruto * 0.015, 2);
        $irrf = 0.00; 
        $valePedagio = 150.00; 
        
        $taxaPlataforma = round($freteBruto * 0.05, 2); // 5% do 123fretei
        
        // O que sobra líquido
        $liquido = $freteBruto - $inss - $sestSenat - $irrf - $taxaPlataforma + $valePedagio;

        return new CiotResponseDTO(
            sucesso: true,
            codigoCiot: 'MOCK-' . strtoupper(Str::random(10)),
            bruto: $freteBruto,
            inss: $inss,
            sestSenat: $sestSenat,
            irrf: $irrf,
            valePedagio: $valePedagio,
            taxa123fretei: $taxaPlataforma,
            liquidoMotorista: $liquido,
            payloadOriginal: ['status' => 'approved_by_mock']
        );
    }

    public function cancelarCiot(string $codigoCiot): bool
    {
        Log::info("[MOCK PEF] Simulando cancelamento do CIOT {$codigoCiot}");
        return true;
    }

    public function liquidarFrete(string $codigoCiot): bool
    {
        Log::info("[MOCK PEF] Simulando liquidação final para o CIOT {$codigoCiot}");
        return true;
    }
}