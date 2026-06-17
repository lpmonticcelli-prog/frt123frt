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
            
            // ZT-DEFENSE: Payload Encriptado em Repouso (AES-256)
            $table->text('cpf');
            $table->string('cpf_bidx', 64)->unique(); // O(1) Search & Uniqueness 
            
            $table->text('cnh');
            $table->string('cnh_bidx', 64)->unique(); // O(1) Search & Uniqueness 
            
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