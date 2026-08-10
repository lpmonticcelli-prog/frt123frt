<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos_escrow', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos Estruturais
            $table->foreignId('carga_id')->constrained('cargas')->cascadeOnDelete();
            $table->foreignId('embarcador_id')->constrained('embarcadores')->cascadeOnDelete();
            
            // Defesa contra Dupla Cobrança (Double-Spending) e Idempotência
            $table->string('idempotency_key', 128)->unique();
            $table->string('gateway_tx_id')->nullable()->unique()->comment('ID da transação no Gateway');
            
            // Matemática Financeira B2B (Muralha Anti-Bitributação)
            $table->decimal('valor_total', 10, 2)->comment('O que o cliente paga (PIX)');
            $table->decimal('split_plataforma', 10, 2)->comment('O que a 123fretei fatura (SaaS)');
            $table->decimal('split_motorista', 10, 2)->comment('O que vai para o CIOT e Tag de Pedágio');
            
            // Payload de Pagamento
            $table->text('qr_code_payload')->nullable();
            $table->string('qr_code_url')->nullable();
            
            // Máquina de Estados
            $table->enum('status', ['aguardando_pagamento', 'liquidado', 'falhou', 'estornado'])->default('aguardando_pagamento');
            
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos_escrow');
    }
};