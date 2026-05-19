<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carga_telemetria_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carga_id')->constrained('cargas')->cascadeOnDelete();
            $table->foreignId('motorista_id')->constrained('motoristas')->cascadeOnDelete();
            
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->decimal('heading', 5, 2)->default(0);
            
            $table->timestamp('registrado_em');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['carga_id', 'registrado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carga_telemetria_logs');
    }
};