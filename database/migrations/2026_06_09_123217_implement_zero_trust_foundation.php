<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone_bidx')) {
                $table->string('phone_bidx', 64)->unique()->nullable()->after('phone')->comment('HMAC-SHA256 O(1)');
            }
        });

        Schema::table('motoristas', function (Blueprint $table) {
            if (!Schema::hasColumn('motoristas', 'cpf_bidx')) {
                $table->string('cpf_bidx', 64)->unique()->nullable()->after('cpf')->comment('HMAC-SHA256 O(1)');
                $table->string('cnh_bidx', 64)->unique()->nullable()->after('cnh')->comment('HMAC-SHA256 O(1)');
                $table->string('rntrc_bidx', 64)->unique()->nullable()->after('rntrc')->comment('HMAC-SHA256 O(1)');
                $table->string('gr_referencia_bidx', 64)->unique()->nullable()->after('gr_referencia')->comment('HMAC-SHA256 O(1)');
            }
        });

        Schema::table('embarcadores', function (Blueprint $table) {
            if (!Schema::hasColumn('embarcadores', 'cnpj_bidx')) {
                $table->string('cnpj_bidx', 64)->unique()->nullable()->after('cnpj')->comment('HMAC-SHA256 O(1)');
                
                // Camada de Dark Room A1 Certs
                $table->string('certificado_a1_path', 512)->nullable()->after('inscricao_estadual')->comment('Ponteiro de FileSystem Isolado Dark Room');
                $table->text('certificado_a1_senha')->nullable()->after('certificado_a1_path')->comment('AES-256-GCM');
                $table->timestamp('certificado_a1_vencimento')->nullable()->after('certificado_a1_senha');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_bidx');
        });

        Schema::table('motoristas', function (Blueprint $table) {
            $table->dropColumn(['cpf_bidx', 'cnh_bidx', 'rntrc_bidx', 'gr_referencia_bidx']);
        });

        Schema::table('embarcadores', function (Blueprint $table) {
            $table->dropColumn(['cnpj_bidx', 'certificado_a1_path', 'certificado_a1_senha', 'certificado_a1_vencimento']);
        });
    }
};