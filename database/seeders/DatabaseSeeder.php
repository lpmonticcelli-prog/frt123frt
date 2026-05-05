<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Cria os perfis estruturais e básicos do sistema (Garante que existem sempre, mesmo em Produção)
        Role::firstOrCreate(['slug' => 'embarcador'], ['name' => 'Embarcador / Indústria']);
        Role::firstOrCreate(['slug' => 'motorista'], ['name' => 'Motorista']);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Sócio / Administrador (Root)']);
        Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Gestor de Operações']);
        Role::firstOrCreate(['slug' => 'compliance'], ['name' => 'Analista KYC']);
        Role::firstOrCreate(['slug' => 'suporte_n1'], ['name' => 'Suporte Operacional']);

        // 2. Chama a injeção do cenário de Homologação (Dados de Teste)
        // Dica: Quando o sistema for para Produção real, basta comentar ou apagar a linha abaixo.
        $this->call([
            HomologacaoSeeder::class,
        ]);
    }
}