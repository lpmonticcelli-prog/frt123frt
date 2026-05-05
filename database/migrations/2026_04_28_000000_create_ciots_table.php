<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciots', function (Blueprint $table) {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            
            // Relacionamentos Fortes
            $table->foreignId('carga_id')->constrained('cargas')->onDelete('cascade');
            $table->foreignId('embarcador_id')->constrained('embarcadores')->onDelete('cascade');
            $table->foreignId('motorista_id')->nullable()->constrained('motoristas')->onDelete('set null');
            
            // Estado e Auditoria
            $table->string('codigo_ciot')->nullable();
            $table->string('status')->default('processando');
            
            // Core Financeiro
            $table->decimal('valor_frete_bruto', 10, 2);
            $table->decimal('imposto_inss', 10, 2)->default(0);
            $table->decimal('imposto_sest_senat', 10, 2)->default(0);
            $table->decimal('imposto_irrf', 10, 2)->default(0);
            $table->decimal('valor_vale_pedagio', 10, 2)->default(0);
            $table->decimal('taxa_123fretei', 10, 2)->default(0);
            $table->decimal('valor_frete_liquido', 10, 2);
            
            // Trilha de Logs B2B
            $table->json('pef_payload_response')->nullable();
            $table->json('webhook_payload')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciots');
    }
};
