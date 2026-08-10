<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antt_tabelas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_carga'); // Ex: Geral, Granel, Frigorificada, Perigosa
            $table->integer('eixos'); // De 2 a 9 eixos
            $table->decimal('coeficiente_deslocamento', 10, 4); // Custo por KM rodado (R$)
            $table->decimal('coeficiente_carga_descarga', 10, 4); // Custo fixo (R$)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antt_tabelas');
    }
};