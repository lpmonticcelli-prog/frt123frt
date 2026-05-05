<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            // Idempotência: Só cria se não existir
            if (!Schema::hasColumn('cargas', 'foto_canhoto')) {
                $table->string('foto_canhoto')->nullable()->comment('Caminho seguro (LGPD) da foto do canhoto');
            }
            
            if (!Schema::hasColumn('cargas', 'foto_carga')) {
                $table->string('foto_carga')->nullable()->comment('Caminho seguro (LGPD) da foto da carga');
            }

            // A coluna vital para o Motor de Liquidação Automática
            if (!Schema::hasColumn('cargas', 'em_auditoria_desde')) {
                $table->timestamp('em_auditoria_desde')->nullable()->comment('Gatilho de inicio do SLA de liquidacao');
                $table->index('em_auditoria_desde');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            if (Schema::hasColumn('cargas', 'em_auditoria_desde')) {
                $table->dropIndex(['em_auditoria_desde']);
                $table->dropColumn('em_auditoria_desde');
            }
        });
    }
};
