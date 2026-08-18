<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AnttTabela;

class AnttTabelaSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa a tabela antes de popular para não duplicar
        DB::table('antt_tabelas')->truncate();

        // MATRIZ BLINDADA - Portaria SUROC nº 4
        // Cobre 100% dos eixos (2 a 9) para Geral e Frigorificada
        $dados = [
            // === CARGA GERAL ===
            ['tipo_carga' => 'Geral', 'eixos' => 2, 'coeficiente_deslocamento_km' => 3.33, 'coeficiente_carga_descarga' => 157.75],
            ['tipo_carga' => 'Geral', 'eixos' => 3, 'coeficiente_deslocamento_km' => 4.32, 'coeficiente_carga_descarga' => 214.98],
            ['tipo_carga' => 'Geral', 'eixos' => 4, 'coeficiente_deslocamento_km' => 5.57, 'coeficiente_carga_descarga' => 272.53],
            ['tipo_carga' => 'Geral', 'eixos' => 5, 'coeficiente_deslocamento_km' => 6.59, 'coeficiente_carga_descarga' => 325.78],
            ['tipo_carga' => 'Geral', 'eixos' => 6, 'coeficiente_deslocamento_km' => 7.94, 'coeficiente_carga_descarga' => 382.80],
            ['tipo_carga' => 'Geral', 'eixos' => 7, 'coeficiente_deslocamento_km' => 9.49, 'coeficiente_carga_descarga' => 440.24],
            ['tipo_carga' => 'Geral', 'eixos' => 8, 'coeficiente_deslocamento_km' => 10.15,'coeficiente_carga_descarga' => 471.00], 
            ['tipo_carga' => 'Geral', 'eixos' => 9, 'coeficiente_deslocamento_km' => 10.82,'coeficiente_carga_descarga' => 503.13],

            // === CARGA FRIGORIFICADA ===
            ['tipo_carga' => 'Frigorificada', 'eixos' => 2, 'coeficiente_deslocamento_km' => 4.10, 'coeficiente_carga_descarga' => 188.67],
            ['tipo_carga' => 'Frigorificada', 'eixos' => 3, 'coeficiente_deslocamento_km' => 5.22, 'coeficiente_carga_descarga' => 262.05],
            ['tipo_carga' => 'Frigorificada', 'eixos' => 4, 'coeficiente_deslocamento_km' => 6.45, 'coeficiente_carga_descarga' => 330.18], 
            ['tipo_carga' => 'Frigorificada', 'eixos' => 5, 'coeficiente_deslocamento_km' => 7.68, 'coeficiente_carga_descarga' => 398.31],
            ['tipo_carga' => 'Frigorificada', 'eixos' => 6, 'coeficiente_deslocamento_km' => 9.06, 'coeficiente_carga_descarga' => 461.20],
            ['tipo_carga' => 'Frigorificada', 'eixos' => 7, 'coeficiente_deslocamento_km' => 10.55,'coeficiente_carga_descarga' => 522.40],
            ['tipo_carga' => 'Frigorificada', 'eixos' => 8, 'coeficiente_deslocamento_km' => 11.20,'coeficiente_carga_descarga' => 580.00], 
            ['tipo_carga' => 'Frigorificada', 'eixos' => 9, 'coeficiente_deslocamento_km' => 12.10,'coeficiente_carga_descarga' => 610.15], 
        ];

        foreach ($dados as $item) {
            AnttTabela::create($item);
        }
    }
}