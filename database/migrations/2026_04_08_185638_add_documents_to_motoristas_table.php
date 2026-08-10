<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('motoristas', function (Blueprint $table) {
            // ZT-DEFENSE: Extensão de capacidade para encriptação de caminhos lógicos no disco
            $table->text('doc_cnh')->nullable()->after('is_disponivel');
            $table->text('doc_rntrc')->nullable()->after('doc_cnh');
            $table->text('doc_comprovante_endereco')->nullable()->after('doc_rntrc');
            
            $table->enum('status_verificacao', ['pendente', 'em_analise', 'aprovado', 'rejeitado'])->default('pendente')->after('doc_comprovante_endereco');
        });
    }

    public function down()
    {
        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn(['doc_cnh', 'doc_rntrc', 'doc_comprovante_endereco', 'status_verificacao']);
        });
    }
};