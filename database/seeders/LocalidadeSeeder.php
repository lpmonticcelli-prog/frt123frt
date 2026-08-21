<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LocalidadeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📥 Baixando Malha Geográfica Completa (IBGE + Coordenadas)...');

        DB::table('cidades')->delete();
        DB::table('estados')->delete();

        $estadosApi = Http::timeout(60)->withoutVerifying()->get('https://servicodados.ibge.gov.br/api/v1/localidades/estados')->json();
        
        if (!$estadosApi) {
            $this->command->error('❌ Falha ao contatar a API do IBGE para baixar os estados.');
            return;
        }

        $estadosMap = [];
        foreach ($estadosApi as $est) {
            $estadoId = DB::table('estados')->insertGetId([
                'nome' => $est['nome'],
                'uf' => $est['sigla'],
                'codigo_ibge' => $est['id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $estadosMap[$est['id']] = $estadoId;
        }
        $this->command->info('✅ Todos os Estados foram criados.');

        $this->command->info('🗺️ Baixando base de Cidades via CDN (jsDelivr)...');

        // URL alterada para a CDN: Maior velocidade e envia o header 'application/json' corretamente
        $cidadesUrl = 'https://cdn.jsdelivr.net/gh/kelvins/municipios-brasileiros@main/json/municipios.json';
        
        $response = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
                        ->timeout(60)
                        ->withoutVerifying()
                        ->get($cidadesUrl);

        // Limpa impurezas (BOM) do texto que podem quebrar o leitor de JSON
        $body = preg_replace('/^[\xef\xbb\xbf]/', '', $response->body());
        $cidadesApi = json_decode($body, true);

        if (empty($cidadesApi)) {
            $this->command->error("❌ Falha no Parse do JSON! Erro do PHP: " . json_last_error_msg());
            return;
        }

        $cidadesLote = [];
        $contador = 0;

        foreach ($cidadesApi as $cid) {
            if (!isset($estadosMap[$cid['codigo_uf']])) {
                continue;
            }

            $cidadesLote[] = [
                'estado_id' => $estadosMap[$cid['codigo_uf']],
                'nome' => $cid['nome'],
                'codigo_ibge' => $cid['codigo_ibge'],
                'latitude' => $cid['latitude'],
                'longitude' => $cid['longitude'],
                'created_at' => now(),
                'updated_at' => now()
            ];

            if (count($cidadesLote) === 500) {
                DB::table('cidades')->insert($cidadesLote);
                $cidadesLote = [];
                $contador += 500;
                $this->command->info("   -> {$contador} cidades processadas...");
            }
        }

        if (!empty($cidadesLote)) {
            DB::table('cidades')->insert($cidadesLote);
            $contador += count($cidadesLote);
            $this->command->info("   -> {$contador} cidades processadas (FIM).");
        }

        $this->command->info('🎯 Malha 100% atualizada! O Perto de Mim vai funcionar no Brasil inteiro.');
    }
}