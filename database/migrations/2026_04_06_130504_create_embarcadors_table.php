<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Forçamos o nome 'embarcadores' para alinhar com o Controller
        Schema::create('embarcadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            
            $table->string('razao_social', 150);
            $table->string('cnpj', 18)->unique();
            $table->string('inscricao_estadual', 30)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embarcadores');
    }
};