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
            
            // Chaves Estrangeiras (Relacionamentos)
            $table->foreignId('embarcador_id')->constrained('embarcadores')->onDelete('cascade');
            $table->foreignId('motorista_id')->nullable()->constrained('motoristas')->onDelete('set null');

            // Dados Operacionais
            $table->string('produto');
            $table->string('especie');
            $table->decimal('peso_kg', 10, 2); // Nome corrigido
            $table->decimal('cubagem_m3', 8, 2)->nullable();

            // Requisitos Logísticos (Adicionados)
            $table->string('tipo_veiculo');
            $table->string('tipo_carroceria');

            // Rotas (Nomes corrigidos para o padrão do frontend)
            $table->string('cidade_origem');
            $table->string('uf_origem', 2);
            $table->string('cidade_destino');
            $table->string('uf_destino', 2);
            $table->decimal('distancia_km', 10, 2)->nullable();

            // Financeiro
            $table->decimal('valor_frete', 10, 2);
            $table->decimal('taxa_plataforma', 10, 2)->default(0);
            
            // Controle de Fluxo
            $table->string('status')->default('disponivel');

            // Agendamentos (Nomes corrigidos)
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