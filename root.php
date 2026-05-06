<?php
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "⚙️ INICIANDO INJEÇÃO NATIVA DO ROOT...\n";

$roleId = DB::table('roles')->where('slug', 'admin')->value('id');

$user = User::firstOrCreate(
    ['email' => 'dev@123fretei.com.br'],
    [
        'name' => 'DevOps Root',
        'password' => Hash::make('password'),
        'role_id' => $roleId ?: 1,
        'phone' => '11900000000'
    ]
);

// Força a substituição da senha para aniquilar qualquer hash corrompido anterior
$user->password = Hash::make('password');
$user->save();

echo "✅ CREDENCIAL ROOT ESTABILIZADA E ATIVA!\n";
echo "👉 Login: " . $user->email . "\n";
echo "👉 Senha: password\n";