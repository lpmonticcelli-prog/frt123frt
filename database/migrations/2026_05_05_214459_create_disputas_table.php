<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputas', function (Blueprint $table) {
            $table->id();
            // Integridade Referencial: Se a carga for deletada, a disputa morre junto (Cascade)
            $table->foreignId('carga_id')->constrained('cargas')->onDelete('cascade');
            $table->string('motivo');
            $table->string('status')->default('aberta')->comment('aberta, em_analise, resolvida');
            $table->text('resolucao')->nullable()->comment('Parecer final da auditoria');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputas');
    }
};
