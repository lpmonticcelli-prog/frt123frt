<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->string('transat_referencia')->nullable()->index()->after('status')->comment('UUID do Dossiê na GR');
            $table->string('transat_biometria_url')->nullable()->after('transat_referencia')->comment('Link gerado dinamicamente para Selfie');
            $table->json('transat_laudo_raw')->nullable()->after('transat_biometria_url')->comment('Guarda o retorno bruto para auditorias jurídicas');
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->dropColumn(['transat_referencia', 'transat_biometria_url', 'transat_laudo_raw']);
        });
    }
};