<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            if (!Schema::hasColumn('motoristas', 'rntrc_bidx')) {
                $table->string('rntrc_bidx')->nullable()->index()->after('cnh_bidx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('motoristas', function (Blueprint $table) {
            if (Schema::hasColumn('motoristas', 'rntrc_bidx')) {
                $table->dropColumn('rntrc_bidx');
            }
        });
    }
};
