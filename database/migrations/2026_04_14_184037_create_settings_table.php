<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value');
            $table->timestamps();
        });

        // Injeta os valores padrão na criação da tabela
        DB::table('settings')->insert([
            ['key' => 'taxa_plataforma', 'value' => '5.00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'tempo_limite_aceite_minutos', 'value' => '15', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dias_faturamento', 'value' => '15', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};