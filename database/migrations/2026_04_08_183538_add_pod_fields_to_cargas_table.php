<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->string('foto_canhoto')->nullable()->after('status');
            $table->string('foto_carga')->nullable()->after('foto_canhoto');
        });
    }

    public function down()
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->dropColumn(['foto_canhoto', 'foto_carga']);
        });
    }
};