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
        Schema::table('users', function (Blueprint $table) {
            $table->string('termo_versao')->nullable()->comment('Ex: v2026-09.02');
            $table->ipAddress('termo_ip')->nullable();
            $table->text('termo_user_agent')->nullable();
            $table->timestamp('termo_aceite_em')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'termo_versao',
                'termo_ip',
                'termo_user_agent',
                'termo_aceite_em'
            ]);
        });
    }
};