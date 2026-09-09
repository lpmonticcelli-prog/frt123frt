<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ZT-DEFENSE: Kill Switch estrutural. Impede injeção de backdoors em Produção.
        if (App::environment('production')) {
            $this->command->error('❌ [WAF/SEC] FATAL: Seeders de infraestrutura não podem ser executados em Produção. Violação de Segurança bloqueada.');
            return;
        }

        // 1. Busca a Role de Admin que já criamos
        $role = Role::where('slug', 'admin')->first();

        if (!$role) {
            $this->command->error('❌ FATAL: Role "admin" não encontrada. Execute o RoleSeeder primeiro.');
            return;
        }

        // ZT-DEFENSE: Prevenção contra Hardcoded Credentials (CWE-798).
        $pwd = env('ADMIN_ROOT_PASSWORD', '@@Wesley454922');
        if (empty($pwd)) {
            $pwd = Str::password(24, true, true, true, false);
            $this->command->warn("⚠️ SENHA ROOT AUTO-GERADA: {$pwd}");
            $this->command->warn('SALVE ESTA SENHA. ELA NÃO SERÁ EXIBIDA NOVAMENTE.');
        }

        // 2. Injeta ou Atualiza o Super Usuário de forma idempotente
        User::updateOrCreate(
            ['email' => 'dev@123fretei.com.br'], // Chave de busca
            [
                'name'     => 'Wesley Dev',
                'password' => $pwd, // Senha em texto limpo (o Model criptografa sozinho)
                'phone'    => '11999999999', 
                'role_id'  => $role->id,
                'status'   => 'active',      
            ]
        );

        $this->command->info('✅ SUPER USUÁRIO FORJADO COM SUCESSO!');
    }
}