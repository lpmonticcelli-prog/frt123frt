<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Força a exclusão da tabela em cascata no PostgreSQL (quebrando as chaves estrangeiras)
        DB::statement('DROP TABLE IF EXISTS faturas CASCADE;');

        Schema::create('faturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('embarcador_id')->constrained('embarcadores')->onDelete('cascade');
            $table->string('mes_referencia'); // Ex: "04/2026"
            $table->decimal('valor_total', 10, 2);
            $table->enum('status', ['pendente', 'paga', 'cancelada', 'vencida'])->default('pendente');
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->string('link_boleto')->nullable(); // Para futura integração com Pagar.me/Stripe
            $table->json('detalhes_cargas')->nullable(); // Guardar quais IDs de carga compõem esta fatura
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS faturas CASCADE;');
    }
};