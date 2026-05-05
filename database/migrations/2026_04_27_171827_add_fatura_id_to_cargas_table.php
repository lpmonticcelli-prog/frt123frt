<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->foreignId('fatura_id')->nullable()->constrained('faturas')->onDelete('set null')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->dropForeign(['fatura_id']);
            $table->dropColumn('fatura_id');
        });
    }
};