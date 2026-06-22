<?php

declare(strict_types=1);

namespace App\Services\Partners;

use App\Contracts\RiskManagementInterface;
use Illuminate\Support\Facades\Log;

class BypassRiskManager implements RiskManagementInterface
{
    public function consultarRisco(array $dadosMotorista, array $dadosVeiculos): array
    {
        Log::info('[GR STRATEGY] Consulta bypassada pela injeção de dependência. Retornando aprovação imediata.');
        
        return [
            'status' => 'aprovado',
            'motivo' => 'DigitalOcean Bypass Ativo',
            'protocolo' => 'BYPASS-' . uniqid()
        ];
    }

    public function registrarViagem($carga): bool
    {
        return true;
    }
}