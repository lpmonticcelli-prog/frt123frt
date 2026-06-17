<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embarcadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            
            $table->string('razao_social', 150);
            
            // ZT-DEFENSE: Payload Encriptado em Repouso (AES-256)
            $table->text('cnpj');
            // ZT-DEFENSE: Blind Index para Busca O(1) e Trava contra Sybil Attacks.
            // Impede Duplicidade de Cadastro B2B preservando a criptografia forte do CNPJ.
            $table->string('cnpj_bidx', 64)->unique();
            
            $table->text('inscricao_estadual')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embarcadores');
    }
};