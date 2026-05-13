<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('embarcadores', function (Blueprint $table) {
            $table->decimal('taxa_frete_percentual', 5, 2)->nullable()->after('cnpj');
            $table->decimal('mensalidade_fixa', 10, 2)->nullable()->after('taxa_frete_percentual'); 
        });
    }

    public function down(): void
    {
        Schema::table('embarcadores', function (Blueprint $table) {
            $table->dropColumn(['taxa_frete_percentual', 'mensalidade_fixa']);
        });
    }
};