<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('motorista_seguros', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com a tabela de motoristas (assumindo que a tabela se chama 'motoristas')
            $table->foreignId('motorista_id')->constrained('motoristas')->onDelete('cascade');
            
            // Identificador único da apólice/certificado retornado pela Iza
            $table->string('iza_policy_id')->nullable()->unique()->index();
            
            // Plano escolhido baseado na cotação: 'basico' (34,90), 'intermediario' (59,90) ou 'superior' (99,90)
            $table->string('plano'); 
            
            // Controle de estado: pendente_emissao, ativo, inadimplente, cancelado
            $table->string('status')->default('pendente_emissao')->index();
            
            // Controle de vigência mensal
            $table->dateTime('data_inicio_vigencia')->nullable();
            $table->dateTime('data_fim_vigencia')->nullable();
            
            // Armazenamento do payload cru da IZA para auditoria e troubleshooting
            $table->json('payload_retorno')->nullable();
            
            $table->timestamps();
            
            // Soft deletes é crucial em integrações financeiras/seguros para manter histórico
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motorista_seguros');
    }
};