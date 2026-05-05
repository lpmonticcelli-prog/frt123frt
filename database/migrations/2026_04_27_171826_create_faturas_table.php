<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('embarcador_id')->constrained('embarcadores')->onDelete('cascade');
            $table->string('codigo_referencia')->unique(); // Ex: FAT-202604-001
            $table->date('data_vencimento');
            $table->decimal('valor_fretes', 10, 2)->default(0);
            $table->decimal('valor_taxas', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->enum('status', ['aberta', 'paga', 'vencida', 'cancelada'])->default('aberta');
            $table->string('nfe_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faturas');
    }
};