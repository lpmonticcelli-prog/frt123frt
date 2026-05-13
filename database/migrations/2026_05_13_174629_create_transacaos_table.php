<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motorista_id')->constrained('motoristas')->onDelete('cascade');
            $table->foreignId('carga_id')->nullable()->constrained('cargas')->onDelete('set null');
            $table->enum('tipo', ['credito', 'debito']);
            $table->decimal('valor', 10, 2);
            $table->string('descricao');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes');
    }
};