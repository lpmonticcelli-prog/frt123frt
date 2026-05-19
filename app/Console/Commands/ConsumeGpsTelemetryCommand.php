<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ConsumeGpsTelemetryCommand extends Command
{
    protected $signature = 'telemetria:consume';
    protected $description = 'Worker ACID para ingestão de streams GPS (Microserviço Go -> Redis -> PostgreSQL)';

    public function handle(): int
    {
        $streamName = 'gps_tracking_stream';
        $groupName = 'laravel_telemetry_group';
        $consumerName = 'worker_' . getmypid();

        // Extrai a conexão nativa de alta performance (PhpRedis)
        $redis = Redis::connection()->client();

        try {
            // Regista o Consumer Group apontando para o início do stream ('0')
            // O 'true' força a criação do stream caso o Go ainda não tenha emitido nada
            $redis->xGroup('CREATE', $streamName, $groupName, '0', true);
        } catch (Exception $e) {
            // Ignora a exceção se o grupo já existir (Erro de BUSYGROUP do Redis)
            if (!str_contains($e->getMessage(), 'BUSYGROUP')) {
                Log::error("[Telemetria] Falha catastrófica ao injetar Consumer Group: " . $e->getMessage());
                return 1;
            }
        }

        $this->info("⚡ [TELEMETRIA] Motor de Ingestão Ativado. Grupo: {$groupName} | Consumer: {$consumerName}");

        while (true) {
            try {
                // XREADGROUP: Trava a execução por 2 segundos esperando dados. 
                // Extrai pacotes de no máximo 100 coordenadas por loop.
                $messages = $redis->xReadGroup($groupName, $consumerName, [$streamName => '>'], 100, 2000);

                if (!$messages || empty($messages[$streamName])) {
                    continue; // Sleep natural do timeout. Nenhum caminhão moveu-se.
                }

                $batchInsert = [];
                $messageIds = [];

                foreach ($messages[$streamName] as $messageId => $payload) {
                    $batchInsert[] = [
                        'carga_id'      => (int) $payload['carga_id'],
                        'motorista_id'  => (int) $payload['driver_id'],
                        'lat'           => (float) $payload['lat'],
                        'lng'           => (float) $payload['lng'],
                        'heading'       => (float) $payload['heading'],
                        'registrado_em' => Carbon::createFromTimestamp((int) $payload['timestamp'])->toDateTimeString(),
                        'created_at'    => Carbon::now()->toDateTimeString(),
                    ];
                    // Guarda o ID do Redis para executar o ACKnowledge e limpar a RAM
                    $messageIds[] = $messageId; 
                }

                // INSERÇÃO O(1): Executa dezenas de pings num único comando SQL
                if (!empty($batchInsert)) {
                    DB::table('carga_telemetria_logs')->insert($batchInsert);
                    
                    // Liberta a carga do Redis (O microserviço Go agradece)
                    $redis->xAck($streamName, $groupName, $messageIds);
                    
                    $this->line("[" . now()->format('H:i:s') . "] 📡 " . count($batchInsert) . " logs de GPS persistidos no cofre.");
                }

            } catch (Exception $e) {
                Log::error("[Telemetria] Falha de Loop no Worker: " . $e->getMessage());
                // Circuit Breaker: Evita que o loop infinito queime a CPU caso o banco/redis caia
                sleep(3); 
            }
        }

        return 0;
    }
}