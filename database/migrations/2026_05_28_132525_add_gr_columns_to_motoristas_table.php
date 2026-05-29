<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            // ZT-DEFENSE: Colunas de Auditoria da Gerenciadora de Risco
            $table->enum('gr_status', [
                'nao_solicitado',
                'pendente',
                'aprovado',
                'rejeitado',
                'aguardando_biometria'
            ])->default('nao_solicitado')->after('score_geral');
            
            $table->uuid('gr_referencia')->nullable()->after('gr_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn(['gr_status', 'gr_referencia']);
        });
    }
};