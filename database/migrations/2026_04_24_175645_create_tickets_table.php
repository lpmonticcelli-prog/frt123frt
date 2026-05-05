<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('carga_id')->nullable()->constrained('cargas')->nullOnDelete();
            
            $table->string('assunto');
            $table->string('categoria'); // Financeiro, Disputa, Dúvida Técnica
            $table->enum('prioridade', ['baixa', 'normal', 'alta', 'urgente'])->default('normal');
            $table->enum('status', ['aberto', 'em_atendimento', 'aguardando_cliente', 'resolvido', 'fechado'])->default('aberto');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};