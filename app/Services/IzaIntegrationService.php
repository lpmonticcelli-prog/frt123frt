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
}