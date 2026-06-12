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
            
            // ZT-DEFENSE: Convertido para text para suportar o Payload AES-256 e Unique removido
            $table->text('cnpj');
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