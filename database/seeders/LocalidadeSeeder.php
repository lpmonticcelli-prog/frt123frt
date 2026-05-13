<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LocalidadeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📥 Transferindo malha de municípios do IBGE para o Banco Local...');

        $estados = Http::timeout(30)->withoutVerifying()->get('https://servicodados.ibge.gov.br/api/v1/localidades/estados')->json();

        foreach ($estados as $est) {
            $estadoId = DB::table('estados')->insertGetId([
                'nome' => $est['nome'],
                'uf' => $est['sigla'],
                'codigo_ibge' => $est['id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $cidades = Http::timeout(30)->withoutVerifying()->get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$est['id']}/municipios")->json();
            
            $cidadesLote = [];
            foreach ($cidades as $cid) {
                $cidadesLote[] = [
                    'estado_id' => $estadoId,
                    'nome' => $cid['nome'],
                    'codigo_ibge' => $cid['id'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            
            DB::table('cidades')->insert($cidadesLote);
            $this->command->info("✅ {$est['nome']} sincronizado!");
        }
    }
}