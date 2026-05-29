<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| 🔒 ZERO TRUST: Rotas de Simulação (Mock Server)
|--------------------------------------------------------------------------
*/

Route::prefix('api/mock/transat')->group(function () {
    
    // 1. Simula o Endpoint de Autenticação Keycloak da GR
    // Keycloak exige form-urlencoded, portanto validamos isso aqui.
    Route::post('realms/one/protocol/openid-connect/token', function (Request $request) {
        
        $username = $request->input('username');
        $password = $request->input('password');

        if (!$username || !$password) {
            Log::channel('single')->error('[MOCK] Keycloak Rejeitou: Faltam credenciais', $request->all());
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Missing parameter: username'
            ], 401);
        }

        Log::channel('single')->info('[MOCK] TransSat Login Aprovado para: ' . $username);
        
        return response()->json([
            'access_token' => 'mock_token_zero_trust_' . time(),
            'expires_in' => 3600,
            'refresh_expires_in' => 1800,
            'token_type' => 'Bearer',
            'not-before-policy' => 0,
            'session_state' => 'mock-session-id',
            'scope' => 'email profile'
        ], 200);
    });

    // 2. Simula Endpoints de Inserção (Carga/Motorista)
    Route::post('{any}', function (Request $request, string $any) {
        Log::channel('single')->info("[MOCK] TransSat Recebeu POST para /{$any}", $request->all());
        
        return response()->json([
            'sucesso' => true,
            'mensagem' => 'MOCK: Dados recebidos e integrados com sucesso.',
            'referencia' => 'GR-' . rand(100000, 999999),
            'status' => 'em_analise_mock'
        ], 200);
    })->where('any', '.*');

    // 3. Simula Endpoints de Consulta
    Route::get('{any}', function (Request $request, string $any) {
        return response()->json([
            'sucesso' => true,
            'status' => 'aprovado',
            'laudo' => 'MOCK-LIBERADO'
        ], 200);
    })->where('any', '.*');
});