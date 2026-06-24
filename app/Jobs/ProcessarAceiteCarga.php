<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Carga;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessarAceiteCarga implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected int $cargaId, 
        protected int $motoristaUserId, 
        protected string $ipAddress, 
        protected string $userAgent
    ) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                // ROW-LEVEL LOCKING: Mantemos a trava para evitar que dois motoristas ganhem a mesma carga no mesmo milissegundo
                $carga = Carga::where('id', $this->cargaId)->lockForUpdate()->first();

                if (!$carga || $carga->status !== 'processando_aceite') {
                    Log::warning("[Worker/Match] Processamento abortado: Carga {$this->cargaId} já teve match realizado ou status mutado.");
                    return;
                }

                $motoristaUser = User::with('motorista')->findOrFail($this->motoristaUserId);
                
                if (!$motoristaUser->motorista || $motoristaUser->status !== 'active') {
                    Log::error("[Worker/Compliance] Match bloqueado: Usuário ou Perfil de Motorista inativo.");
                    // Devolve para o mural de cargas disponíveis
                    $carga->status = 'publicada';
                    $carga->save();
                    return;
                }

                // EFETIVAÇÃO DO MATCH (Tinder do Frete)
                $carga->motorista_id = $motoristaUser->motorista->id;
                $carga->status = 'match_realizado';
                $carga->save();

                // ZT-DEFENSE: Termo de Match (Blindagem Jurídica)
                // Removemos o vocabulário de "Contrato de Transporte" e assumimos o papel de "Software de Lead/Match"
                $termoMatch = "TERMO DE MATCH (LEAD). Pelo presente aceite eletrônico, a plataforma 123fretei consolida a aproximação entre o motorista {$motoristaUser->name} e o embarcador responsável pela carga ID {$carga->id}. A plataforma atua estritamente como provedora de tecnologia e matchmaking, não possuindo responsabilidade solidária, subsidiária ou securitária sobre a execução do transporte, repasse de pagamentos, avarias na mercadoria ou emissão de documentos fiscais (CIOT/MDF-e).";

                $termoHash = hash('sha256', $termoMatch . $this->ipAddress . time());

                // Guardamos o log do momento exato do Match (útil para justificar a cobrança da sua taxa ou assinatura)
                DB::table('carga_aceites_log')->insert([
                    'carga_id' => $carga->id,
                    'motorista_id' => $motoristaUser->motorista->id,
                    'ip_address' => $this->ipAddress,
                    'user_agent' => $this->userAgent,
                    'termo_hash' => $termoHash,
                    'aceito_em' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("[Worker/Match] Match efetivado com sucesso. Carga {$carga->id} conectada ao Motorista ID {$motoristaUser->motorista->id}. Contatos liberados.");
            });
        } catch (Throwable $e) {
            Log::critical("[Worker/Fatal] Falha atômica ao processar match da carga {$this->cargaId}: " . $e->getMessage());
            throw $e;
        }
    }
}