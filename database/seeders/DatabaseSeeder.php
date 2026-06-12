<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Role;
use App\Models\Embarcador;
use App\Models\Motorista;
use App\Models\Carga;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pt_BR');
        $now = Carbon::now();

        // ZT-DEFENSE: Passada a limpo. Não requer hash prévio devido ao mutator `$casts = ['password' => 'hashed']`
        $plainPassword = env('SEED_DEFAULT_PASSWORD');
        if (empty($plainPassword)) {
            $plainPassword = Str::password(16, true, true, false, false);
            $this->command->warn("⚠️ A SENHA PADRÃO GERADA PARA ESTE AMBIENTE É: {$plainPassword}");
            $this->command->warn('SALVE ESTA SENHA. ELA NÃO SERÁ EXIBIDA NOVAMENTE.');
        }

        $this->command->info('🚀 Iniciando População Massiva Enterprise (Eloquent Strict)...');

        DB::beginTransaction();

        try {
            $roleAdmin = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador']);
            $roleEmb = Role::firstOrCreate(['slug' => 'embarcador'], ['name' => 'Embarcador']);
            $roleMot = Role::firstOrCreate(['slug' => 'motorista'], ['name' => 'Motorista']);

            $admin = User::firstOrCreate(
                ['email' => 'admin@123fretei.com'],
                [
                    'name' => 'Wesley Admin',
                    'password' => $plainPassword,
                    'role_id' => $roleAdmin->id,
                    'phone' => '11999999990',
                    'status' => 'active'
                ]
            );

            $this->command->info('⚙️ Gerando 10 Embarcadores (Criptografados)...');
            $embarcadorIds = [];
            for ($i = 1; $i <= 10; $i++) {
                $user = User::firstOrCreate(
                    ['email' => "embarcador{$i}@empresa.com"],
                    [
                        'name' => $faker->company,
                        'password' => $plainPassword,
                        'role_id' => $roleEmb->id,
                        'phone' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('119########')),
                        'status' => 'active'
                    ]
                );

                $embarcador = Embarcador::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'razao_social' => $user->name . ' LTDA',
                        'cnpj' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('##############')),
                    ]
                );
                
                $embarcadorIds[] = $embarcador->id;
            }

            $this->command->info('⚙️ Gerando 30 Motoristas (Criptografados)...');
            $motoristaModelIds = [];
            for ($i = 1; $i <= 30; $i++) {
                $user = User::firstOrCreate(
                    ['email' => "motorista{$i}@estrada.com"],
                    [
                        'name' => $faker->name,
                        'password' => $plainPassword,
                        'role_id' => $roleMot->id,
                        'phone' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('118########')),
                        'status' => 'active'
                    ]
                );
                
                $motorista = Motorista::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'cpf' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('###########')),
                        'cnh' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('###########')),
                        'validade_cnh' => now()->addYears(5)->toDateString(),
                        'rntrc' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('########')),
                        'is_disponivel' => true,
                        'status_verificacao' => 'aprovado',
                        'gr_status' => 'aprovado'
                    ]
                );

                $motoristaModelIds[] = $motorista->id; 
            }

            $this->command->info('⚙️ Gerando 200 Cargas...');
            $cargas = [];
            for ($i = 0; $i < 200; $i++) {
                $cargas[] = [
                    'embarcador_id' => $faker->randomElement($embarcadorIds),
                    'motorista_id' => $faker->boolean(40) ? $faker->randomElement($motoristaModelIds) : null,
                    'produto' => $faker->words(2, true),
                    'especie' => $faker->randomElement(['Caixas', 'Paletes', 'Granel', 'Sacas']),
                    'peso_kg' => $faker->randomFloat(2, 500, 30000),
                    'tipo_veiculo' => $faker->randomElement(['Truck', 'Carreta', 'Toco', 'VUC', 'Fiorino']),
                    'tipo_carroceria' => $faker->randomElement(['Baú', 'Sider', 'Grade Baixa', 'Câmara Fria']),
                    'cidade_origem' => $faker->city,
                    'uf_origem' => strtoupper($faker->lexify('??')), // Fake UF genérico
                    'cidade_destino' => $faker->city,
                    'uf_destino' => strtoupper($faker->lexify('??')),
                    'valor_frete' => $faker->randomFloat(2, 500, 15000),
                    'taxa_plataforma' => 50,
                    'data_coleta' => $faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d H:i:s'),
                    'status' => $faker->randomElement(['publicada', 'em_transito', 'concluida', 'cancelada']),
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            
            // Cargas não possuem criptografia at-rest, usamos DB::table para contornar gargalo e overhead do Reverb.
            foreach (array_chunk($cargas, 50) as $chunk) {
                DB::table('cargas')->insert($chunk);
            }

            $this->command->info('⚙️ Gerando Disputas e Tickets...');
            $cargaIdsArray = DB::table('cargas')->pluck('id')->toArray();
            if (!empty($cargaIdsArray)) {
                for ($i = 0; $i < 15; $i++) {
                    DB::table('disputas')->insert([
                        'carga_id' => $faker->randomElement($cargaIdsArray),
                        'motivo' => $faker->sentence(),
                        'status' => $faker->randomElement(['aberta', 'em_analise', 'resolvida']),
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
            }

            $todosUsuariosIds = User::pluck('id')->toArray();

            for ($i = 0; $i < 20; $i++) {
                DB::table('tickets')->insert([
                    'user_id' => $faker->randomElement($todosUsuariosIds),
                    'staff_id' => $faker->randomElement([$admin->id, null]),
                    'carga_id' => $faker->randomElement(array_merge($cargaIdsArray, [null])),
                    'assunto' => $faker->sentence(3),
                    'categoria' => $faker->randomElement(['Operacional', 'Financeiro', 'Aplicativo', 'Dúvida']),
                    'prioridade' => $faker->randomElement(['baixa', 'normal', 'alta', 'urgente']),
                    'status' => $faker->randomElement(['aberto', 'em_atendimento', 'aguardando_cliente', 'resolvido', 'fechado']),
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            DB::commit();
            $this->command->info('✅ BANCO DE DADOS POPULADO COM SUCESSO! CLOUD NATIVE E ZERO TRUST READY.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ ERRO FATAL NO SEEDER: ' . $e->getMessage());
        }
    }
}