<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnttTabelaSeeder extends Seeder
{
    public function run(): void
    {
        $tabela = [
            // Carga Geral (Valores base aproximados para testes)
            ['tipo_carga' => 'Geral', 'eixos' => 2, 'coeficiente_deslocamento' => 3.50, 'coeficiente_carga_descarga' => 150.00],
            ['tipo_carga' => 'Geral', 'eixos' => 3, 'coeficiente_deslocamento' => 4.20, 'coeficiente_carga_descarga' => 200.00],
            ['tipo_carga' => 'Geral', 'eixos' => 4, 'coeficiente_deslocamento' => 5.10, 'coeficiente_carga_descarga' => 250.00],
            ['tipo_carga' => 'Geral', 'eixos' => 5, 'coeficiente_deslocamento' => 6.00, 'coeficiente_carga_descarga' => 300.00],
            ['tipo_carga' => 'Geral', 'eixos' => 6, 'coeficiente_deslocamento' => 6.80, 'coeficiente_carga_descarga' => 350.00],
            ['tipo_carga' => 'Geral', 'eixos' => 7, 'coeficiente_deslocamento' => 7.50, 'coeficiente_carga_descarga' => 400.00],
            ['tipo_carga' => 'Geral', 'eixos' => 9, 'coeficiente_deslocamento' => 9.00, 'coeficiente_carga_descarga' => 500.00],
            
            // Frigorificada
            ['tipo_carga' => 'Frigorificada', 'eixos' => 6, 'coeficiente_deslocamento' => 8.20, 'coeficiente_carga_descarga' => 450.00],
        ];

        DB::table('antt_tabelas')->insert($tabela);
    }
}