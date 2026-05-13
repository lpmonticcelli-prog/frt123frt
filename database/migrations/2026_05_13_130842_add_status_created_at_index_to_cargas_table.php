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
        Schema::table('cargas', function (Blueprint $table) {
            // Criação do índice composto otimizado para a malha de leitura do Motorista
            $table->index(['status', 'created_at'], 'idx_cargas_status_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            // Reversão segura nomeando explicitamente o índice
            $table->dropIndex('idx_cargas_status_created_at');
        });
    }
};