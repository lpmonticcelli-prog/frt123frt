<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            $table->enum('gr_status', [
                'nao_solicitado',
                'pendente',
                'aprovado',
                'rejeitado',
                'aguardando_biometria'
            ])->default('nao_solicitado')->after('score_geral');
            
            // ZT-DEFENSE: A referência do dossiê GR é criptografada. UUID e String causarão Exception.
            $table->text('gr_referencia')->nullable()->after('gr_status');
        });
    }

    public function down(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn(['gr_status', 'gr_referencia']);
        });
    }
};