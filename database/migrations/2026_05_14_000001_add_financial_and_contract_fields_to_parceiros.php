<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            // Inteligência Financeira
            $table->decimal('valor_cobrado', 12, 2)->default(0)->after('ordem_exibicao');
            $table->enum('modelo_cobranca', ['unico', 'mensal', 'anual', 'por_clique'])->default('mensal')->after('valor_cobrado');
            
            // Inteligência Temporal (Duração do Anúncio/Contrato)
            $table->date('data_inicio')->nullable()->after('modelo_cobranca');
            $table->date('data_fim')->nullable()->after('data_inicio');
            
            // Rastreabilidade e Auditoria
            $table->string('codigo_contrato_externo')->nullable()->after('data_fim'); // Link com o seu ERP/Contabilidade
            $table->integer('cliques_acumulados')->default(0)->after('codigo_contrato_externo');
            $table->string('posicionamento')->default('hub_geral')->comment('dashboard_top, hub_geral, modal_pos_frete');
            
            // Status de Pagamento do Parceiro para o 123fretei
            $table->enum('status_financeiro', ['em_dia', 'pendente', 'cancelado', 'cortesia'])->default('em_dia')->after('posicionamento');
        });
    }

    public function down(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            $table->dropColumn([
                'valor_cobrado', 'modelo_cobranca', 'data_inicio', 'data_fim', 
                'codigo_contrato_externo', 'cliques_acumulados', 'posicionamento', 'status_financeiro'
            ]);
        });
    }
};