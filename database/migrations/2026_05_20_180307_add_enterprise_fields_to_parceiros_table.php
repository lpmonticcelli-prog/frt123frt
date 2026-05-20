<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            // Adicionamos APENAS as colunas que realmente faltam na sua base de dados
            $table->integer('dias_duracao')->default(15);
            $table->timestamp('data_ativacao')->nullable();
            $table->timestamp('data_expiracao')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            $table->dropColumn([
                'dias_duracao', 
                'data_ativacao', 
                'data_expiracao'
            ]);
        });
    }
};