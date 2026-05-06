<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Injeção limpa e segura dos cargos estruturais obrigatórios
        DB::table('roles')->insertOrIgnore([
            [
                'name' => 'Administrador', 
                'slug' => 'admin', 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'name' => 'Motorista', 
                'slug' => 'motorista', 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'name' => 'Embarcador', 
                'slug' => 'embarcador', 
                'created_at' => now(), 
                'updated_at' => now()
            ]
        ]);
    }
}