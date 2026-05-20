<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Quebra a trava antiga do modelo de cobrança
        DB::statement('ALTER TABLE parceiros DROP CONSTRAINT IF EXISTS parceiros_modelo_cobranca_check');
        
        // Quebra a trava antiga do status financeiro (para garantir que não bloqueia o próximo campo)
        DB::statement('ALTER TABLE parceiros DROP CONSTRAINT IF EXISTS parceiros_status_financeiro_check');
        
        // Quebra a trava da categoria (caso você tenha mudado opções lá também)
        DB::statement('ALTER TABLE parceiros DROP CONSTRAINT IF EXISTS parceiros_categoria_check');
    }

    public function down(): void
    {
        // Não é necessário reverter esta operação
    }
};