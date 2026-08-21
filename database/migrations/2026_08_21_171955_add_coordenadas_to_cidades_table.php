<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cidades', function (Blueprint $table) {
            // Adiciona as colunas de coordenadas aceitando até 8 casas decimais (padrão GPS)
            $table->decimal('latitude', 11, 8)->nullable()->after('codigo_ibge');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('cidades', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};