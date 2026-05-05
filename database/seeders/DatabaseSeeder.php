<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Faker\Factory as Faker;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('pt_BR');
        $now = Carbon::now();

        $this->command->info('🚀 Iniciando População Massiva Enterprise - 123fretei...');

        DB::beginTransaction();

        try {
            // 1. DOMÍNIO DE AUTORIZAÇÃO (ROLES)
            $roles = [
                ['name' => 'Administrador', 'slug' => 'admin'],
                ['name' => 'Embarcador', 'slug' => 'embarcador'],
                ['name' => 'Motorista', 'slug' => 'motorista']
            ];

            $roleIds = [];
            foreach ($roles as $role) {
                $roleIds[$role['slug']] = DB::table('roles')->where('slug', $role['slug'])->value('id') 
                    ?: DB::table('roles')->insertGetId(array_merge($role, ['created_at' => $now, 'updated_at' => $now]));
            }

            // 2. DOMÍNIO DE USUÁRIOS (STAFF)
            $adminEmail = 'admin@123fretei.com';
            $adminUserId = DB::table('users')->where('email', $adminEmail)->value('id');
            if (!$adminUserId) {
                $adminUserId = DB::table('users')->insertGetId([
                    'name' => 'Wesley Admin',
                    'email' => $adminEmail,
                    'password' => Hash::make('123mudar'),
                    'role_id' => $roleIds['admin'],
                    'phone' => '11999999990',
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            // 3. DOMÍNIO DE EMBARCADORES (B2B)
            $this->command->info('⚙️ Gerando 10 Embarcadores...');
            $embarcadorIds = [];
            for ($i = 1; $i <= 10; $i++) {
                $email = "embarcador{$i}@empresa.com";
                $userId = DB::table('users')->where('email', $email)->value('id');
                
                if (!$userId) {
                    $userId = DB::table('users')->insertGetId([
                        'name' => $faker->company,
                        'email' => $email,
                        'password' => Hash::make('123mudar'),
                        'role_id' => $roleIds['embarcador'],
                        'phone' => $faker->numerify('119########'),
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);

                    $embarcadorIds[] = DB::table('embarcadores')->insertGetId([
                        'user_id' => $userId,
                        'razao_social' => $faker->company . ' LTDA',
                        'cnpj' => $faker->numerify('##############'),
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                } else {
                    $embarcadorIds[] = DB::table('embarcadores')->where('user_id', $userId)->value('id');
                }
            }

            // 4. DOMÍNIO DE MOTORISTAS (PARCEIROS)
            $this->command->info('⚙️ Gerando 30 Motoristas...');
            $motoristaIds = [];
            for ($i = 1; $i <= 30; $i++) {
                $email = "motorista{$i}@estrada.com";
                $userId = DB::table('users')->where('email', $email)->value('id');
                
                if (!$userId) {
                    $userId = DB::table('users')->insertGetId([
                        'name' => $faker->name,
                        'email' => $email,
                        'password' => Hash::make('123mudar'),
                        'role_id' => $roleIds['motorista'],
                        'phone' => $faker->numerify('119########'),
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
                $motoristaIds[] = $userId;
            }

            // 5. DOMÍNIO OPERACIONAL (CARGAS)
            $this->command->info('⚙️ Gerando 200 Cargas...');
            $cargas = [];
            for ($i = 0; $i < 200; $i++) {
                $cargas[] = [
                    'embarcador_id' => $faker->randomElement($embarcadorIds),
                    'produto' => $faker->words(2, true),
                    'especie' => $faker->randomElement(['Caixas', 'Paletes', 'Granel', 'Sacas']),
                    'peso_kg' => $faker->randomFloat(2, 500, 30000),
                    'tipo_veiculo' => $faker->randomElement(['Truck', 'Carreta', 'Toco', 'VUC', 'Fiorino']),
                    'tipo_carroceria' => $faker->randomElement(['Baú', 'Sider', 'Grade Baixa', 'Câmara Fria']),
                    'cidade_origem' => $faker->city,
                    'uf_origem' => $faker->stateAbbr,
                    'cidade_destino' => $faker->city,
                    'uf_destino' => $faker->stateAbbr,
                    'valor_frete' => $faker->randomFloat(2, 500, 15000),
                    'data_coleta' => $faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d H:i:s'),
                    'status' => $faker->randomElement(['disponivel', 'em_transito', 'concluida', 'cancelada']),
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            foreach (array_chunk($cargas, 50) as $chunk) {
                DB::table('cargas')->insert($chunk);
            }

            // 6. DOMÍNIO DE DISPUTAS
            $this->command->info('⚙️ Gerando Disputas...');
            $cargaIds = DB::table('cargas')->pluck('id')->toArray();
            if (!empty($cargaIds)) {
                for ($i = 0; $i < 15; $i++) {
                    DB::table('disputas')->insert([
                        'carga_id' => $faker->randomElement($cargaIds),
                        'motivo' => $faker->sentence(),
                        'status' => $faker->randomElement(['aberta', 'em_analise', 'resolvida']),
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
            }

            // 7. DOMÍNIO DE SUPORTE (TICKETS) - Restrições Blindadas
            $this->command->info('⚙️ Gerando Tickets de Suporte...');
            for ($i = 0; $i < 20; $i++) {
                DB::table('tickets')->insert([
                    'user_id' => $faker->randomElement($motoristaIds),
                    'staff_id' => $faker->randomElement([$adminUserId, null]),
                    'carga_id' => $faker->randomElement(array_merge($cargaIds, [null])),
                    'assunto' => $faker->sentence(3),
                    'categoria' => $faker->randomElement(['Operacional', 'Financeiro', 'Aplicativo', 'Dúvida']),
                    'prioridade' => $faker->randomElement(['baixa', 'normal', 'alta', 'urgente']), // Corrigido
                    'status' => $faker->randomElement(['aberto', 'em_atendimento', 'aguardando_cliente', 'resolvido', 'fechado']), // Corrigido
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            DB::commit();
            $this->command->info('✅ BANCO DE DADOS POPULADO COM SUCESSO! CLOUD NATIVE READY.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ ERRO FATAL NO SEEDER: ' . $e->getMessage());
        }
    }
}
