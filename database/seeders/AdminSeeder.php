<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Busca a Role de Admin que já criamos
        $role = Role::where('slug', 'admin')->first();

        if (!$role) {
            $this->command->error('❌ FATAL: Role "admin" não encontrada. Execute o RoleSeeder primeiro.');
            return;
        }

        // 2. Injeta ou Atualiza o Super Usuário de forma idempotente
        User::updateOrCreate(
            ['email' => 'dev@123fretei.com.br'], // Chave de busca
            [
                'name'     => 'Root DevOps',
                'password' => Hash::make('password'),
                'phone'    => '00000000000', // Telefone de sistema (Bypass)
                'role_id'  => $role->id,
                'status'   => 'active',      // Status 'active' bypassa qualquer esteira de aprovação
            ]
        );

        $this->command->info('✅ SUPER USUÁRIO FORJADO COM SUCESSO!');
        $this->command->info('Login: dev@123fretei.com.br | Senha: password');
    }
}