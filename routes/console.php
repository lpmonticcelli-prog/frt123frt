<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Role;
use App\Models\User;
use App\Models\Motorista;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Motor de Liquidação Automática (SLA de Pagamento do Motorista)
Schedule::command('fretei:liquidar-sla')->hourly();

// ==========================================
// DEVSECOPS: ROOT INJECTION (ELOQUENT SAFE)
// ==========================================
Artisan::command('forge:root', function () {
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador']);
    
    // ZT-DEFENSE: UpdateOrCreate usa a malha Eloquent, ativando BlindIndexService e o Cast de Hash
    $user = User::updateOrCreate(
        ['email' => 'dev@123fretei.com.br'],
        [
            'name' => 'DevOps Root',
            'password' => 'password', 
            'role_id' => $role->id,
            'phone' => '11900000000',
            'status' => 'active'
        ]
    );

    $this->info('✅ ALINHAMENTO CRIPTOGRÁFICO DO ROOT CONCLUÍDO!');
});

// ==========================================
// DEVSECOPS: INJEÇÃO DE PILOTO DE TESTE Padrão (MOTORISTA)
// ==========================================
Artisan::command('forge:motorista', function () {
    $role = Role::where('slug', 'motorista')->first();
    
    if (!$role) {
        $this->error('❌ ARQUITETURA INCOMPLETA: A role "motorista" não existe na tabela de cargos.');
        return;
    }

    $user = User::updateOrCreate(
        ['email' => 'piloto@123fretei.com.br'],
        [
            'name' => 'João Estadeiro (Teste)',
            'password' => 'password',
            'role_id' => $role->id,
            'phone' => '11999999999',
            'status' => 'active'
        ]
    );

    Motorista::updateOrCreate(
        ['user_id' => $user->id],
        [
            'cpf' => '00000000000',
            'cnh' => '00000000000',
            'validade_cnh' => now()->addYears(5)->toDateString(),
            'rntrc' => '00000000',
            'status_verificacao' => 'aprovado',
            'gr_status' => 'aprovado'
        ]
    );

    $this->info('✅ PILOTO DE TESTE FORJADO COM SUCESSO! Login: piloto@123fretei.com.br | Senha: password');
});

// ==========================================
// DEVSECOPS: ALINHAMENTO DE RBAC (CORREÇÃO DO MOTORISTA 1)
// ==========================================
Artisan::command('fix:motorista', function () {
    $roleMotorista = Role::where('slug', 'motorista')->first();
    
    if (!$roleMotorista) {
        $this->error('❌ FATAL: A role "motorista" não existe. Suas migrations estão incompletas.');
        return;
    }

    $user = User::where('email', 'motorista1@estrada.com')->first();
    
    if (!$user) {
        $this->error('❌ FATAL: O usuário motorista1@estrada.com não existe no banco.');
        return;
    }

    $user->update([
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
// DEVSECOPS: POPULAR COMPLIANCE DO MOTORISTA DE TESTE
// ==========================================
Artisan::command('forge:compliance-motorista', function () {
    $email = 'motorista1@estrada.com';
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        $this->error("❌ FATAL: O usuário {$email} não existe na tabela users.");
        return;
    }

    Motorista::updateOrCreate(
        ['user_id' => $user->id],
        [
            'cpf' => '12345678900',
            'cnh' => '01234567890',
            'validade_cnh' => now()->addYears(5)->format('Y-m-d'),
            'rntrc' => '12345678',
            'status_verificacao' => 'aprovado',
            'gr_status' => 'aprovado'
        ]
    );

    $this->info('✅ COMPLIANCE ATENDIDO E CRIPTOGRAFADO! A trava Fail-Fast do Controller agora permitirá a passagem.');
});