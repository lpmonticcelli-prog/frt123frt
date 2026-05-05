<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            // Adiciona apenas os campos que não existem, marcando como nullable 
            // para não quebrar registros de testes anteriores.
            
            if (!Schema::hasColumn('cargas', 'tipo_veiculo')) {
                $table->string('tipo_veiculo')->nullable();
            }
            
            if (!Schema::hasColumn('cargas', 'tipo_carroceria')) {
                $table->string('tipo_carroceria')->nullable();
            }

            // Garante que os campos de geolocalização exatos existam
            if (!Schema::hasColumn('cargas', 'uf_origem')) {
                $table->char('uf_origem', 2)->nullable();
            }
            if (!Schema::hasColumn('cargas', 'cidade_origem')) {
                $table->string('cidade_origem')->nullable();
            }
            if (!Schema::hasColumn('cargas', 'uf_destino')) {
                $table->char('uf_destino', 2)->nullable();
            }
            if (!Schema::hasColumn('cargas', 'cidade_destino')) {
                $table->string('cidade_destino')->nullable();
            }
            
            if (!Schema::hasColumn('cargas', 'status')) {
                $table->string('status')->default('disponivel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_veiculo', 
                'tipo_carroceria', 
                'uf_origem', 
                'cidade_origem', 
                'uf_destino', 
                'cidade_destino', 
                'status'
            ]);
        });
    }
};