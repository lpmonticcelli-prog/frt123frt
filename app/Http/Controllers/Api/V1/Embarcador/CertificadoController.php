<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Security\DarkRoomService;
use Illuminate\Support\Facades\Log;
use Exception;

class CertificadoController extends Controller
{
    public function __construct(private readonly DarkRoomService $darkRoomService) {}

    public function upload(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->role || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            Log::alert("[WAF] Violação de contexto A1 (Spoofing).", ['ip' => $request->ip()]);
            return response()->json(['error' => 'Acesso negado no Gateway de API.'], 403);
        }

        $request->validate([
            'certificado' => 'required|file|mimes:pfx,p12|max:5120',
            'senha' => 'required|string|max:255'
        ]);

        try {
            $this->darkRoomService->trancarCertificado($user->embarcador, $request->file('certificado'), $request->senha);
            
            Log::info("SecOps: Certificado A1 alocado no Dark Room. Embarcador ID: {$user->embarcador->id}");

            return response()->json([
                'message' => 'Ativo fiscal isolado, validado e bloqueado na custódia criptográfica.'
            ], 200);

        } catch (Exception $e) {
            Log::alert("WAF Intercept: Upload A1 falhou. Motivo: {$e->getMessage()}", ['ip' => $request->ip()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}