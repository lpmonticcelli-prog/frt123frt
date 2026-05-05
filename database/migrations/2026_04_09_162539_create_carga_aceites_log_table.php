<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('carga_aceites_log', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos Fortes
            $table->foreignId('carga_id')->constrained('cargas')->onDelete('cascade');
            $table->foreignId('motorista_id')->constrained('motoristas')->onDelete('cascade');
            
            // Dados de Auditoria Jurídica (Obrigatórios para validade legal)
            $table->string('ip_address');
            $table->text('user_agent'); // Qual navegador/celular foi usado
            $table->string('termo_hash'); // Assinatura criptográfica (SHA-256) do texto lido
            $table->timestamp('aceito_em'); // Data e hora exatas pelo relógio do servidor
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('carga_aceites_log');
    }
};