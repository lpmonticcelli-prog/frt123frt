<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->decimal('lat_origem', 10, 8)->nullable()->after('cidade_origem');
            $table->decimal('lng_origem', 10, 8)->nullable()->after('lat_origem');
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->dropColumn(['lat_origem', 'lng_origem']);
        });
    }
};