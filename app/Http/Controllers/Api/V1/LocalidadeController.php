<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class LocalidadeController extends Controller
{
    public function estados(): JsonResponse
    {
        try {
            $estados = Cache::remember('zt_estados_list', 86400, function () {
                // Tenta puxar do banco. Se não existir, previne o crash
                return DB::table('localidades')
                    ->select('uf')
                    ->distinct()
                    ->orderBy('uf')
                    ->pluck('uf');
            });
            return response()->json($estados);
        } catch (Throwable $e) {
            Log::error('[API] Falha ao carregar Estados do DB', ['error' => $e->getMessage()]);
            // Fallback seguro caso a tabela esteja vazia ou não exista
            $fallback = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
            return response()->json($fallback);
        }
    }

    public function municipios(string $uf): JsonResponse
    {
        $ufStr = strtoupper($uf);

        try {
            $municipios = Cache::remember("zt_municipios_list_{$ufStr}", 86400, function () use ($ufStr) {
                return DB::table('localidades')
                    ->where('uf', $ufStr)
                    ->orderBy('cidade')
                    ->get(['id', 'cidade', 'codigo_ibge']);
            });
            return response()->json($municipios);
        } catch (Throwable $e) {
            Log::error("[API] Falha ao carregar Municipios para {$ufStr}", ['error' => $e->getMessage()]);
            return response()->json([]);
        }
    }

    public function buscarCep(string $cep): JsonResponse
    {
        $cepLimpo = preg_replace('/[^0-9]/', '', $cep);
        
        if (strlen($cepLimpo) !== 8) {
            return response()->json(['error' => 'Formato de CEP inválido.'], 422);
        }

        $cacheKey = "zt_cep_lookup:{$cepLimpo}";
        
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey), 200);
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cepLimpo}/json/");
            
            if ($response->failed() || isset($response->json()['erro'])) {
                return response()->json(['error' => 'CEP não localizado.'], 404);
            }

            $dadosCep = $response->json();
            Cache::put($cacheKey, $dadosCep, now()->addDays(30));
            return response()->json($dadosCep, 200);

        } catch (Throwable $e) {
            return response()->json(['error' => 'Serviço de consulta de endereço temporariamente indisponível.'], 503);
        }
    }
}