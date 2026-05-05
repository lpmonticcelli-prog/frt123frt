<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motoristas', function (Blueprint $table) {
            $table->id();
            
            // Chave estrangeira ligando o motorista ao usuário base (login/senha)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Dados Documentais Exigidos pelo AuthController
            $table->string('cpf', 14)->unique();
            $table->string('cnh', 20)->unique();
            $table->date('validade_cnh');
            $table->string('rntrc', 15)->unique(); // Registro Nacional de Transportadores (ANTT)
            
            // Controle de Status Operacional
            $table->boolean('is_disponivel')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motoristas');
    }
};