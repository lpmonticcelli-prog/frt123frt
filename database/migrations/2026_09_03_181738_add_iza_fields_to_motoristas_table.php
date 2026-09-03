<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            $table->string('seguro_iza_id')->nullable()->after('rntrc');
            $table->string('seguro_iza_status')->default('inativo')->after('seguro_iza_id');
            $table->timestamp('seguro_iza_vencimento')->nullable()->after('seguro_iza_status');
            $table->string('seguro_iza_plano')->nullable()->after('seguro_iza_vencimento');
        });
    }

    public function down(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn(['seguro_iza_id', 'seguro_iza_status', 'seguro_iza_vencimento', 'seguro_iza_plano']);
        });
    }
};