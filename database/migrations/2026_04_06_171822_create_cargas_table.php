<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargas', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('embarcador_id')->constrained('embarcadores')->onDelete('cascade');
            $table->foreignId('motorista_id')->nullable()->constrained('motoristas')->onDelete('set null');

            $table->string('produto');
            $table->string('especie');
            $table->decimal('peso_kg', 10, 2);
            $table->decimal('cubagem_m3', 8, 2)->nullable();

            $table->string('tipo_veiculo');
            $table->string('tipo_carroceria');

            $table->string('cidade_origem');
            $table->string('uf_origem', 2);
            $table->string('cidade_destino');
            $table->string('uf_destino', 2);
            $table->decimal('distancia_km', 10, 2)->nullable();

            $table->decimal('valor_frete', 10, 2);
            $table->decimal('taxa_plataforma', 10, 2)->default(0);
            
            // CIRURGIA APLICADA: Inicialização como "publicada" alinhado ao Frontend e Backend
            $table->string('status')->default('publicada');

            $table->date('data_coleta');
            $table->dateTime('data_entrega_prevista')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargas');
    }
};