<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LocalidadeController extends Controller
{
    public function estados(): JsonResponse
    {
        try {
            // Busca direto do banco formatando para array limpo
            return response()->json(DB::table('estados')->orderBy('uf')->pluck('uf')->toArray());
        } catch (Throwable $e) {
            return response()->json(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']);
        }
    }

    public function municipios(string $uf): JsonResponse
    {
        try {
            $estado = DB::table('estados')->where('uf', strtoupper($uf))->first();
            if (!$estado) return response()->json([]);

            $colunaRelacao = Schema::hasColumn('cidades', 'estado_id') ? 'estado_id' : 'uf';
            $valor = $colunaRelacao === 'estado_id' ? $estado->id : strtoupper($uf);
            $colunaNome = Schema::hasColumn('cidades', 'nome') ? 'nome' : 'cidade';

            $cidades = DB::table('cidades')
                ->where($colunaRelacao, $valor)
                ->orderBy($colunaNome)
                ->get();
            
            $lista = $cidades->map(function($c) use ($colunaNome) {
                return [
                    'id' => $c->id,
                    'cidade' => $c->{$colunaNome},
                    'codigo_ibge' => $c->codigo_ibge ?? $c->ibge_code ?? null
                ];
            })->toArray();
            
            return response()->json($lista);
        } catch (Throwable $e) {
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
            return response()->json(['error' => 'Serviço temporariamente indisponível.'], 503);
        }
    }
}
