<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class IzaIntegrationService
{
    public function contratarPlano($motorista, $plano)
    {
        // TODO: Futura integração real com a IZA
        /*
        $payload = [
            'document' => $motorista->cpf,
            'plan_id'  => $plano === 'iza_basico' ? env('IZA_BASIC') : env('IZA_PRO')
        ];
        $response = Http::withToken(env('IZA_TOKEN'))->post('https://api.iza.com.vc/...', $payload);
        return $response->json('policy_id');
        */

        // MOCK ATUAL: Simula que a IZA aprovou em 1 segundo
        sleep(1); 
        $policyId = 'IZA-MOCK-' . strtoupper(uniqid());
        
        Log::info('[IZA MOCK] Simulação de apólice gerada.', ['motorista_id' => $motorista->id, 'policy' => $policyId]);

        return $policyId;
    }

    /**
     * Cancela uma apólice ativa na IZA Seguradora.
     * 
     * @param string|null $policyId O ID da apólice retornado pela IZA na contratação
     * @return bool
     */
    public function cancelarPlano(?string $policyId): bool
    {
        // TODO: Futura integração real de cancelamento com a IZA
        /*
        $response = Http::withToken(env('IZA_TOKEN'))->delete('https://api.iza.com.vc/policies/' . $policyId);
        return $response->successful();
        */

        // MOCK ATUAL: Simula que a IZA processou o cancelamento em 1 segundo
        sleep(1); 
        
        Log::info('[IZA MOCK] Simulação de cancelamento de apólice.', [
            'policy_id' => $policyId ?? 'SEM-ID'
        ]);

        return true; 
    }
}