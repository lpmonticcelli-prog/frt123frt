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
        Schema::create('antt_tabelas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_carga'); // Ex: Geral, Frigorificada
            $table->integer('eixos'); // Ex: 2, 3, 4... até 9
            
            // Usamos decimal com 4 casas após a vírgula para não perder precisão nos centavos da ANTT
            $table->decimal('coeficiente_deslocamento_km', 10, 4); 
            $table->decimal('coeficiente_carga_descarga', 10, 4); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antt_tabelas');
    }
};