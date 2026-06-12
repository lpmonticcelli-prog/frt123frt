<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('motoristas', function (Blueprint $table) {
            // ZT-DEFENSE: Convertido para text para suportar o Payload AES-256
            $table->text('doc_selfie_cnh')->nullable()->after('doc_cnh');
        });
    }

    public function down()
    {
        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn('doc_selfie_cnh');
        });
    }
};