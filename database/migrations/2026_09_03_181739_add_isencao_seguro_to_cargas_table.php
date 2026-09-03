<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->boolean('isencao_seguro_aceite')->default(false)->after('status');
            $table->string('isencao_seguro_ip', 45)->nullable()->after('isencao_seguro_aceite');
            $table->timestamp('isencao_seguro_data')->nullable()->after('isencao_seguro_ip');
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->dropColumn(['isencao_seguro_aceite', 'isencao_seguro_ip', 'isencao_seguro_data']);
        });
    }
};