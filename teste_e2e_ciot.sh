#!/bin/bash

echo "[123fretei] Ajustando Driver de Filas e Iniciando Testes..."

# Força o ambiente a rodar as filas sincronicamente para o teste
docker-compose exec app sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear

# Criamos um script PHP on-the-fly que rodará dentro do container
cat << 'SCRIPT_PHP' > runner_e2e.php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n[1/3] Procurando dados para o teste...\n";
$carga = \App\Models\Carga::where('status', 'disponivel')->first();
$motorista = \App\Models\User::whereHas('motorista')->first();

if (!$carga || !$motorista) {
    die("ERRO: O banco não tem nenhuma Carga 'disponivel' ou Motorista cadastrado para realizar o teste.\n");
}

echo "[2/3] Simulando o Aceite do Motorista (Sincrono)...\n";
// Disparando sem o .onQueue('default') porque estamos em sync
\App\Jobs\ProcessarAceiteCarga::dispatchSync($carga, $motorista, '127.0.0.1', 'CLI-Test');
echo " -> Carga {$carga->id} alocada. Ignição do CIOT processada no banco.\n";

echo "\n[3/3] Simulando o Webhook de Retorno (ANTT / Pamcard)...\n";
$ciot = \App\Models\Ciot::latest()->first();

if(!$ciot) {
    die("ERRO FATAL: O CIOT não foi criado. A transação do banco falhou ou sofreu rollback.\n");
}

echo " -> UUID de Idempotência capturado: {$ciot->idempotency_key}\n";

$request = Illuminate\Http\Request::create('/api/v1/webhooks/pef', 'POST', [
    'idempotency_key' => $ciot->idempotency_key,
    'status' => 'EMITIDO_ANTT',
    'protocolo' => '123456789'
]);

config(['services.pef.webhook_secret' => 'token_de_teste']);
$request->headers->set('X-PEF-Signature', 'token_de_teste');

$controller = app(\App\Http\Controllers\Api\V1\Webhooks\PefWebhookController::class);
$response = $controller->handle($request);

echo " -> Resposta do Webhook: " . $response->getContent() . "\n";
echo "\nTestes finalizados.\n";
SCRIPT_PHP

# Limpa logs antigos
docker-compose exec app truncate -s 0 storage/logs/laravel.log

# Executa o ciclo dentro do container
docker-compose exec app php runner_e2e.php

# Exibe o resultado dos logs
echo -e "\n=== LOGS GERADOS NO SISTEMA (laravel.log) ==="
docker-compose exec app cat storage/logs/laravel.log

# Limpa o arquivo temporário
rm runner_e2e.php
