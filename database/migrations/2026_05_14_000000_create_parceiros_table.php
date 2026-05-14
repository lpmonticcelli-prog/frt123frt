<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parceiros', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Nome da marca ou produto
            
            // Categoria: propaganda, anuncio, contrato_comercial, produto
            $table->string('categoria')->index(); 
            
            // Público: motorista, embarcador, todos
            $table->enum('audience', ['motorista', 'embarcador', 'todos'])->default('todos')->index();
            
            $table->text('descricao')->nullable();
            $table->string('imagem_url')->nullable(); // Banner ou foto do produto
            $table->string('link_url')->nullable();   // Link externo (ex: site do parceiro)
            
            // Campo para o texto do contrato caso seja do tipo 'contrato_comercial'
            $table->longText('conteudo_contrato')->nullable(); 
            
            $table->boolean('is_active')->default(true)->index();
            $table->integer('ordem_exibicao')->default(0); // Para ordenar os banners
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parceiros');
    }
};