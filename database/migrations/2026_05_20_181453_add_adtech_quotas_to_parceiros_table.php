<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            $table->integer('limite_cliques')->nullable()->comment('Usado apenas no modelo CPC');
            $table->integer('limite_conversoes')->nullable()->comment('Usado apenas no modelo CPA');
            $table->integer('conversoes_acumuladas')->default(0)->comment('Rastreio de CPA');
        });
    }

    public function down(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            $table->dropColumn(['limite_cliques', 'limite_conversoes', 'conversoes_acumuladas']);
        });
    }
};