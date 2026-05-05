<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "Injetando dados no banco de homologacao...\n";

// 0. Resolve a Restrição de Chave Estrangeira (Role)
$idRoleEmb = DB::table('roles')->where('slug', 'embarcador')->value('id');
if (!$idRoleEmb) {
    $idRoleEmb = DB::table('roles')->insertGetId(['name' => 'Embarcador', 'slug' => 'embarcador', 'created_at' => now(), 'updated_at' => now()]);
}

$idRoleMot = DB::table('roles')->where('slug', 'motorista')->value('id');
if (!$idRoleMot) {
    $idRoleMot = DB::table('roles')->insertGetId(['name' => 'Motorista', 'slug' => 'motorista', 'created_at' => now(), 'updated_at' => now()]);
}

// Randomização
$timestamp = time();
$randPhoneEmb = '119' . rand(10000000, 99999999);
$randPhoneMot = '118' . rand(10000000, 99999999);
$randCnpj = (string) rand(10000000000000, 99999999999999);
$randCpf = (string) rand(10000000000, 99999999999);
$randRntrc = (string) rand(10000000, 99999999); // RNTRC Gerado

// 1. Cria Usuários Base
$idUserEmb = DB::table('users')->insertGetId([
    'role_id' => $idRoleEmb, 
    'name' => 'Embarcador E2E', 
    'email' => "emb_{$timestamp}@123fretei.com", 
    'phone' => $randPhoneEmb,
    'password' => bcrypt('123456'),
    'created_at' => now(),
    'updated_at' => now()
]);

$idUserMot = DB::table('users')->insertGetId([
    'role_id' => $idRoleMot, 
    'name' => 'Motorista E2E', 
    'email' => "mot_{$timestamp}@123fretei.com", 
    'phone' => $randPhoneMot,
    'password' => bcrypt('123456'),
    'created_at' => now(),
    'updated_at' => now()
]);

// 2. Cria os Perfis de Domínio
$idEmb = DB::table('embarcadores')->insertGetId([
    'user_id' => $idUserEmb,
    'razao_social' => "Industria Siderurgica E2E {$timestamp}",
    'cnpj' => $randCnpj,
    'created_at' => now(),
    'updated_at' => now()
]);

$idMot = DB::table('motoristas')->insertGetId([
    'user_id' => $idUserMot,
    'cpf' => $randCpf,
    'cnh' => $randCpf,
    'validade_cnh' => Carbon::now()->addYears(5)->toDateString(),
    'rntrc' => $randRntrc,
    'created_at' => now(),
    'updated_at' => now()
]);

// 3. Cria a Carga (Gatilho)
DB::table('cargas')->insert([
    'embarcador_id' => $idEmb,
    'produto' => 'Bobinas de Aço',
    'especie' => 'Metal',
    'peso_kg' => 32000,
    'tipo_veiculo' => 'Carreta',
    'tipo_carroceria' => 'Sider',
    'cidade_origem' => 'Guarulhos',
    'uf_origem' => 'SP',
    'cidade_destino' => 'Curitiba',
    'uf_destino' => 'PR',
    'valor_frete' => 4500.00,
    'taxa_plataforma' => 225.00,
    'status' => 'disponivel',
    'data_coleta' => Carbon::now()->toDateString(),
    'created_at' => Carbon::now(),
    'updated_at' => Carbon::now(),
]);

echo "-> OK! Materia-prima gerada com sucesso.\n";
