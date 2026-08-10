<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locais_operacionais', function (Blueprint $table) {
            $table->id();
            // Amarração estrita com o Embarcador
           $table->foreignId('embarcador_id')->constrained('embarcadores')->cascadeOnDelete();
            
            // Opcional: Amarração com a sua tabela do IBGE (Ajuste o nome da coluna se for diferente)
            // $table->foreignId('localidade_id')->nullable()->constrained('localidades');
            
            $table->string('nome_identificador'); // Ex: "Galpão Principal", "Doca 3"
            $table->string('cep', 10);
            $table->string('logradouro');
            $table->string('numero', 50)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro');
            $table->string('cidade');
            $table->char('uf', 2);
            
            // Georreferenciamento para Motor de Preço e Roteirização Futura
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            $table->boolean('is_padrao')->default(false); // Define o endereço de faturamento principal
            
            $table->timestamps();
            $table->softDeletes(); // ZT-Defense: Impede que a deleção quebre o histórico de um frete passado
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locais_operacionais');
    }
};