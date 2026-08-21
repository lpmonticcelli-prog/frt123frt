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

        // Limpa as tabelas para recriar do zero e evitar duplicação (Cuidado em produção!)
        DB::table('cidades')->delete();
        DB::table('estados')->delete();

        // 1. Baixamos a lista de estados para referenciar
        $estadosApi = Http::timeout(60)->withoutVerifying()->get('https://servicodados.ibge.gov.br/api/v1/localidades/estados')->json();
        $estadosMap = [];

        foreach ($estadosApi as $est) {
            $estadoId = DB::table('estados')->insertGetId([
                'nome' => $est['nome'],
                'uf' => $est['sigla'],
                'codigo_ibge' => $est['id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $estadosMap[$est['id']] = $estadoId; // Guarda o ID para relacionar com a cidade
            $this->command->info("✅ Estado criado: {$est['nome']}");
        }

        $this->command->info('🗺️ Injetando Cidades e Coordenadas...');

        // 2. Buscamos um repositório público consolidado do IBGE (que já traz Lat/Lng em JSON)
        // Esse endpoint público no GitHub é mantido pela comunidade e tem as 5570 cidades com as coordenadas do centroide.
        $cidadesUrl = 'https://raw.githubusercontent.com/kelvins/Municipios-Brasileiros/main/json/municipios.json';
        $cidadesApi = Http::timeout(60)->withoutVerifying()->get($cidadesUrl)->json();

        $cidadesLote = [];
        $contador = 0;

        foreach ($cidadesApi as $cid) {
            // Ignora cidades de estados que não conseguimos mapear
            if (!isset($estadosMap[$cid['codigo_uf']])) {
                continue;
            }

            $cidadesLote[] = [
                'estado_id' => $estadosMap[$cid['codigo_uf']],
                'nome' => $cid['nome'],
                'codigo_ibge' => $cid['codigo_ibge'],
                'latitude' => $cid['latitude'],   // A Mágica Acontece Aqui!
                'longitude' => $cid['longitude'], // E Aqui!
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Inserimos de 500 em 500 para não estourar a memória RAM da VPS
            if (count($cidadesLote) === 500) {
                DB::table('cidades')->insert($cidadesLote);
                $cidadesLote = [];
                $contador += 500;
                $this->command->info("   -> {$contador} cidades processadas...");
            }
        }

        // Insere o restinho que sobrou
        if (!empty($cidadesLote)) {
            DB::table('cidades')->insert($cidadesLote);
        }

        $this->command->info('🎯 Malha 100% atualizada! O Perto de Mim vai funcionar no Brasil inteiro.');
    }
}