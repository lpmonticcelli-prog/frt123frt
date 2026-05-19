<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carga_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carga_id')->constrained('cargas')->cascadeOnDelete();
            $table->unsignedBigInteger('remetente_id'); // ID do Embarcador ou Motorista
            $table->string('remetente_tipo'); // 'embarcador' ou 'motorista'
            $table->text('mensagem');
            $table->boolean('lida')->default(false);
            $table->timestamps();

            $table->index(['carga_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carga_mensagens');
    }
};