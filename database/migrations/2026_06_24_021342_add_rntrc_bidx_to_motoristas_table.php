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
        Schema::table('motoristas', function (Blueprint $table) {
            // Adiciona a coluna do Blind Index do RNTRC logo após a da CNH
            $table->string('rntrc_bidx', 64)->nullable()->after('cnh_bidx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn('rntrc_bidx');
        });
    }
};