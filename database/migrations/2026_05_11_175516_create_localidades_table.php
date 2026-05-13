<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->char('uf', 2)->unique();
            $table->integer('codigo_ibge')->nullable();
            $table->timestamps();
        });

        Schema::create('cidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_id')->constrained('estados')->onDelete('cascade');
            $table->string('nome');
            $table->integer('codigo_ibge')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cidades');
        Schema::dropIfExists('estados');
    }
};