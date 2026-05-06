<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Motor de Liquidação Automática (SLA de Pagamento do Motorista)
Schedule::command('fretei:liquidar-sla')->hourly();

// ==========================================
// DEVSECOPS: ROOT INJECTION (LOW-LEVEL BYPASS)
// ==========================================
Artisan::command('forge:root', function () {
    $roleId = DB::table('roles')->where('slug', 'admin')->value('id');
    
    // BYPASS: Usamos DB::table() para interagir com o PostgreSQL no baixo nível.
    DB::table('users')->updateOrInsert(
        ['email' => 'dev@123fretei.com.br'],
        [
            'name' => 'DevOps Root',
            'password' => Hash::make('password'),
            'role_id' => $roleId ?: 1,
            'phone' => '11900000000',
            'created_at' => now(),
            'updated_at' => now()
        ]
    );

    $this->info('✅ ALINHAMENTO CRIPTOGRÁFICO DO ROOT CONCLUÍDO!');
});

// ==========================================
// DEVSECOPS: INJEÇÃO DE PILOTO DE TESTE Padrão (MOTORISTA)
// ==========================================
Artisan::command('forge:motorista', function () {
    $roleId = DB::table('roles')->where('slug', 'motorista')->value('id');
    
    if (!$roleId) {
        $this->error('❌ ARQUITETURA INCOMPLETA: A role "motorista" não existe na tabela de cargos.');
        return;
    }

    DB::table('users')->updateOrInsert(
        ['email' => 'piloto@123fretei.com.br'],
        [
            'name' => 'João Estadeiro (Teste)',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'phone' => '11999999999',
            'created_at' => now(),
            'updated_at' => now()
        ]
    );

    $this->info('✅ PILOTO DE TESTE FORJADO COM SUCESSO! Login: piloto@123fretei.com.br | Senha: password');
});

// ==========================================
// DEVSECOPS: ALINHAMENTO DE RBAC (CORREÇÃO DO MOTORISTA 1)
// ==========================================
Artisan::command('fix:motorista', function () {
    $roleMotorista = DB::table('roles')->where('slug', 'motorista')->first();
    
    if (!$roleMotorista) {
        $this->error('❌ FATAL: A role "motorista" não existe. Suas migrations estão incompletas.');
        return;
    }

    $user = DB::table('users')->where('email', 'motorista1@estrada.com')->first();
    
    if (!$user) {
        $this->error('❌ FATAL: O usuário motorista1@estrada.com não existe no banco.');
        return;
    }

    DB::table('users')->where('email', 'motorista1@estrada.com')->update([
        'role_id' => $roleMotorista->id
    ]);

    $this->info("✅ ALINHAMENTO RBAC CONCLUÍDO! O usuário [motorista1@estrada.com] agora possui a Role ID: {$roleMotorista->id} (Motorista).");
});

// ==========================================
// DEVSECOPS: AUDITORIA DE DADOS (MURAL DE FRETES)
// ==========================================
Artisan::command('audit:cargas', function () {
    $this->info("🔎 INICIANDO VARREDURA NO CORE BUSINESS (CARGAS)...");

    $total = DB::table('cargas')->count();
    
    $statusAgrupados = DB::table('cargas')
        ->select('status', DB::raw('count(*) as total'))
        ->groupBy('status')
        ->get();

    $comMotorista = DB::table('cargas')->whereNotNull('motorista_id')->count();
    $semMotorista = DB::table('cargas')->whereNull('motorista_id')->count();

    $this->warn("📊 ESTATÍSTICAS DO POSTGRESQL:");
    $this->line("- Total Absoluto de Cargas: {$total}");
    $this->line("- Cargas SEM Motorista (Livres): {$semMotorista}");
    $this->line("- Cargas COM Motorista (Alocadas): {$comMotorista}");
    
    $this->warn("\n📊 DISTRIBUIÇÃO DE STATUS:");
    foreach ($statusAgrupados as $s) {
        $this->line("- Status [{$s->status}]: {$s->total} fretes");
    }

    if ($total === 0) {
        $this->error("\n❌ DIAGNÓSTICO: O banco está completamente VAZIO.");
    } else {
        $this->info("\n✅ DIAGNÓSTICO: O banco possui dados.");
    }
});

// ==========================================
// DEVSECOPS: POPULAR COMPLIANCE DO MOTORISTA DE TESTE (SCHEMA ALINHADO)
// ==========================================
Artisan::command('forge:compliance-motorista', function () {
    $email = 'motorista1@estrada.com';
    $user = DB::table('users')->where('email', $email)->first();
    
    if (!$user) {
        $this->error("❌ FATAL: O usuário {$email} não existe na tabela users.");
        return;
    }

    // Injeção cirúrgica: Passa apenas os campos que a sua Migration definiu como NOT NULL
    DB::table('motoristas')->updateOrInsert(
        ['user_id' => $user->id],
        [
            'cpf' => '123.456.789-00',
            'cnh' => '01234567890', // O banco exigiu esta coluna via restrição NOT NULL
            'created_at' => now(),
            'updated_at' => now()
        ]
    );

    $this->info('✅ COMPLIANCE ATENDIDO (SCHEMA VALIDADO)!');
    $this->info("Perfil físico vinculado ao usuário: {$email}.");
    $this->info("A trava Fail-Fast do Controller agora permitirá a passagem.");
});