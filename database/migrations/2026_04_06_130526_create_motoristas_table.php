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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // ZT-DEFENSE: Convertidos para text para suportar o Payload AES-256. Trava Unique removida.
            $table->text('cpf');
            $table->text('cnh');
            $table->text('rntrc'); 
            
            $table->date('validade_cnh');
            $table->boolean('is_disponivel')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motoristas');
    }
};