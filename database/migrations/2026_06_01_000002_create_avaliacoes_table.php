<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carga_id')->constrained('cargas')->cascadeOnDelete();
            
            // CORREÇÃO: Mapeamento explícito da tabela em português
            $table->foreignId('embarcador_id')->constrained('embarcadores')->cascadeOnDelete();
            $table->foreignId('motorista_id')->constrained('motoristas')->cascadeOnDelete();
            
            $table->unsignedTinyInteger('nota_pontualidade');
            $table->unsignedTinyInteger('nota_cuidado');
            $table->unsignedTinyInteger('nota_comunicacao');
            
            $table->boolean('houve_avaria')->default(false);
            $table->decimal('nota_final', 3, 2);
            $table->text('comentarios')->nullable();
            
            $table->timestamps();

            $table->unique('carga_id');
            $table->index(['motorista_id', 'created_at']);
        });

        Schema::table('motoristas', function (Blueprint $table) {
            // Utilizamos soft check caso a coluna já tenha sido inserida em tentativas anteriores
            if (!Schema::hasColumn('motoristas', 'score_geral')) {
                $table->decimal('score_geral', 3, 2)->default(0.00)->after('status_verificacao');
                $table->unsignedInteger('total_viagens')->default(0)->after('score_geral');
                $table->enum('tier_reputacao', ['novato', 'pro', 'elite', 'prime'])->default('novato')->after('total_viagens');
                $table->timestamp('suspenso_ate')->nullable()->after('tier_reputacao');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
        
        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn(['score_geral', 'total_viagens', 'tier_reputacao', 'suspenso_ate']);
        });
    }
};