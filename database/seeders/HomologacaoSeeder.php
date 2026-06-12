<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Embarcador;
use App\Models\Motorista;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Carga;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class HomologacaoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando geração Homologação B2B...');
        
        $faker = Faker::create('pt_BR');
        
        $senhaBase = env('SEED_DEFAULT_PASSWORD');
        if (empty($senhaBase)) {
            $senhaBase = Str::password(16, true, true, false, false);
            $this->command->warn("🔑 Senha global dinâmica gerada: {$senhaBase}");
        }

        DB::transaction(function () use ($faker, $senhaBase) {
            $now = Carbon::now();
            $roles = [
                'admin' => Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador Root']),
                'manager' => Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Gerente Operacional']),
                'compliance' => Role::firstOrCreate(['slug' => 'compliance'], ['name' => 'Compliance e Risco']),
                'suporte_n1' => Role::firstOrCreate(['slug' => 'suporte_n1'], ['name' => 'Suporte N1']),
                'motorista' => Role::firstOrCreate(['slug' => 'motorista'], ['name' => 'Motorista']),
                'embarcador' => Role::firstOrCreate(['slug' => 'embarcador'], ['name' => 'Embarcador'])
            ];

            $staff = [];
            $staff['admin'] = User::firstOrCreate(['email' => 'dev@123fretei.com.br'], ['name' => 'Wesley Dev', 'phone' => '11999999999', 'password' => $senhaBase, 'role_id' => $roles['admin']->id, 'status' => 'active']);
            $staff['manager'] = User::firstOrCreate(['email' => 'gerente@123fretei.com.br'], ['name' => 'Gerente Operações', 'phone' => '11900000001', 'password' => $senhaBase, 'role_id' => $roles['manager']->id, 'status' => 'active']);
            $staff['compliance'] = User::firstOrCreate(['email' => 'risco@123fretei.com.br'], ['name' => 'Auditoria Risco', 'phone' => '11900000002', 'password' => $senhaBase, 'role_id' => $roles['compliance']->id, 'status' => 'active']);
            $staff['n1'] = User::firstOrCreate(['email' => 'n1@123fretei.com.br'], ['name' => 'Atendente N1', 'phone' => '11900000003', 'password' => $senhaBase, 'role_id' => $roles['suporte_n1']->id, 'status' => 'active']);

            $equipeN1 = [$staff['n1']];
            for ($i = 0; $i < 5; $i++) {
                $equipeN1[] = User::create(['name' => "Suporte " . $faker->firstName, 'email' => "suporte{$i}@123fretei.com.br", 'phone' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('119########')), 'password' => $senhaBase, 'role_id' => $roles['suporte_n1']->id, 'status' => 'active']);
            }

            $this->command->info('Gerando Embarcadores...');
            $embarcadores = [];
            for ($i = 0; $i < 30; $i++) {
                $user = User::create(['name' => $faker->company, 'email' => $faker->unique()->companyEmail, 'phone' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('119########')), 'password' => $senhaBase, 'role_id' => $roles['embarcador']->id, 'status' => 'active']);
                $embarcadores[] = Embarcador::create(['user_id' => $user->id, 'cnpj' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('##############')), 'razao_social' => $user->name, 'taxa_frete_percentual' => 5.00]);
            }

            $this->command->info('Gerando Motoristas...');
            $motoristas = [];
            for ($i = 0; $i < 100; $i++) {
                $user = User::create(['name' => $faker->name, 'email' => $faker->unique()->safeEmail, 'phone' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('119########')), 'password' => $senhaBase, 'role_id' => $roles['motorista']->id, 'status' => 'active']);
                $motoristas[] = Motorista::create(['user_id' => $user->id, 'cpf' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('###########')), 'cnh' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('###########')), 'validade_cnh' => $faker->dateTimeBetween('now', '+3 years')->format('Y-m-d'), 'rntrc' => preg_replace('/[^0-9]/', '', $faker->unique()->numerify('########')), 'is_disponivel' => true, 'gr_status' => 'aprovado', 'status_verificacao' => 'aprovado']);
            }

            $this->command->info('Gerando 300 Cargas Silenciosamente...');
            $cargas = [];
            $statusCarga = ['publicada', 'aceita', 'em_transito', 'entregue', 'finalizada', 'em_disputa', 'cancelada'];
            
            Carga::withoutEvents(function () use ($faker, $embarcadores, $motoristas, $statusCarga, &$cargas, $now) {
                for ($i = 0; $i < 300; $i++) {
                    $status = $faker->randomElement($statusCarga);
                    $embarcador = $faker->randomElement($embarcadores);
                    $motorista = in_array($status, ['publicada', 'cancelada']) ? null : $faker->randomElement($motoristas)->id;

                    $cargas[] = Carga::create([
                        'embarcador_id' => $embarcador->id,
                        'motorista_id' => $motorista,
                        'produto' => $faker->words(2, true),
                        'especie' => $faker->randomElement(['Caixas', 'Paletes', 'Sacos', 'Granel']),
                        'peso_kg' => $faker->numberBetween(500, 30000),
                        'tipo_veiculo' => $faker->randomElement(['Toco', 'Truck', 'Carreta LS', 'Bitrem', 'Fiorino']),
                        'tipo_carroceria' => $faker->randomElement(['Baú', 'Sider', 'Grade Baixa', 'Graneleiro']),
                        'cidade_origem' => $faker->city,
                        'uf_origem' => strtoupper($faker->lexify('??')),
                        'cidade_destino' => $faker->city,
                        'uf_destino' => strtoupper($faker->lexify('??')),
                        'valor_frete' => $faker->randomFloat(2, 500, 8000),
                        'taxa_plataforma' => 50,
                        'data_coleta' => $faker->dateTimeBetween('-30 days', '+15 days')->format('Y-m-d'),
                        'status' => $status,
                        'created_at' => $faker->dateTimeBetween('-30 days', 'now')
                    ]);
                }
            });

            $this->command->info('Injetando Tickets...');
            $idsEmbarcadores = array_map(fn($e) => $e->user_id, $embarcadores);
            $idsCargas = array_map(fn($c) => $c->id, $cargas);

            for ($i = 0; $i < 10; $i++) {
                $clienteId = $faker->randomElement($idsEmbarcadores);
                $ticket = Ticket::create([
                    'user_id' => $clienteId,
                    'staff_id' => null,
                    'carga_id' => $faker->randomElement($idsCargas),
                    'assunto' => 'Dúvida Operacional',
                    'categoria' => 'Dúvida',
                    'prioridade' => 'normal',
                    'status' => 'aberto',
                ]);
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $clienteId,
                    'mensagem' => 'Tenho uma dúvida na plataforma.',
                ]);
            }
        });

        $this->command->info('✅ Homologação Concluída. ORM e Criptografia preservados.');
    }
}