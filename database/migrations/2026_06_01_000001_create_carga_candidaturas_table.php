<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carga_candidaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carga_id')->constrained('cargas')->cascadeOnDelete();
            $table->foreignId('motorista_id')->constrained('motoristas')->cascadeOnDelete();
            $table->enum('status', ['pendente', 'aprovada', 'rejeitada', 'expirada', 'cancelada_motorista'])->default('pendente');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['carga_id', 'motorista_id']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carga_candidaturas');
    }
};