<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('carga_publicacoes_log', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('carga_id')->constrained('cargas')->onDelete('cascade');
            
            // Correção cirúrgica: força o Laravel a buscar a tabela 'embarcadores' (em português)
            // em vez do padrão em inglês ('embarcadors').
            $table->foreignId('embarcador_id')->constrained('embarcadores')->onDelete('cascade');
            
            $table->string('ip_address');
            $table->text('user_agent');
            $table->string('termo_hash');
            $table->timestamp('publicado_em');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('carga_publicacoes_log');
    }
};