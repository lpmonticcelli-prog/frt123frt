<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Carga;
use App\Models\Motorista;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TransatWebhookController extends Controller
{
    private const STATUS_GR_APROVADO = 'aprovado';
    private const STATUS_GR_REJEITADO = 'rejeitado';
    private const STATUS_GR_AGUARDANDO_BIOMETRIA = 'aguardando_biometria';

    public function handleCallback(Request $request): JsonResponse
    {
        // 1. BLINDAGEM ZERO-TRUST (Fix de L7/DoS)
        $tokenEsperado = (string) config('services.transat.webhook_secret');
        $bearer = $request->header('Authorization') ?? $request->header('authorization');
        $tokenRecebido = str_replace(['Bearer ', 'bearer '], '', (string) $bearer);

        if (empty($tokenEsperado) || empty($tokenRecebido) || !hash_equals($tokenEsperado, $tokenRecebido)) {
            Log::alert('[WEBHOOK HACK] Tentativa forjada de laudo GR bloqueada no WAF.', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Acesso Negado.'], 401);
        }

        // 2. RECUPERAÇÃO E SANITIZAÇÃO DA REFERÊNCIA
        $referencia = $request->input('referencia');
        if (empty($referencia)) {
            return response()->json(['error' => 'Payload inválido. Referência ausente.'], 400);
        }

        $linhas = $request->input('lines', []);
        $codigoFinal = count($linhas) > 0 ? (int) $linhas[0]['codigo'] : (int) $request->input('codigo');

        try {
            DB::beginTransaction();

            // Bloqueio pessimista para evitar condição de corrida em callbacks duplicados
            $motorista = Motorista::where('gr_referencia', $referencia)->lockForUpdate()->first();
            
            // ZT-DEFENSE: Apenas tentamos atualizar a Carga se a coluna existir no Schema,
            // evitando o crash fatal (SQLSTATE[42703]) que estava a abortar a transação.
            $carga = null;
            if (Schema::hasColumn('cargas', 'gr_referencia')) {
                $carga = Carga::where('gr_referencia', $referencia)->lockForUpdate()->first();
            }

            if (!$carga && !$motorista) {
                DB::rollBack();
                Log::info('[TransSat] Webhook ignorado. Referência orfã.', ['referencia' => $referencia]);
                return response()->json(['status' => 'Ignorado'], 200); // Retorna 200 para a GR parar de enviar
            }

            // 3A. MÁQUINA DE ESTADO IDEMPOTENTE - MOTORISTA (KYC GR)
            if ($motorista) {
                $novoStatus = $this->mapearCodigoGrParaStatusMotorista($codigoFinal);

                // IDEMPOTÊNCIA: Se o motorista já está com esse status, ignoramos o processamento para não onerar o banco.
                if ($motorista->gr_status !== $novoStatus) {
                    $motorista->update(['gr_status' => $novoStatus]);
                    Log::info("[TransSat] Status do Motorista {$motorista->id} atualizado para: {$novoStatus}");
                }
            }

            // 3B. MÁQUINA DE ESTADO IDEMPOTENTE - CARGA (Tracking Legado)
            if ($carga) {
                $novoStatusCarga = $this->mapearCodigoGrParaStatusCarga($codigoFinal);
                $dadosAtualizacao = [
                    'gr_laudo_raw' => json_encode($request->all()) // Guarda o histórico serializado seguro
                ];

                if ($carga->status !== $novoStatusCarga) {
                    $dadosAtualizacao['status'] = $novoStatusCarga;

                    // Desaloca o motorista se o laudo exigir atenção ou der desacordo
                    if (in_array($novoStatusCarga, ['publicada', 'pendente_correcao_gr'], true)) {
                        $dadosAtualizacao['motorista_id'] = null;
                        $dadosAtualizacao['gr_referencia'] = null;
                    }

                    $carga->update($dadosAtualizacao);
                    Log::info("[TransSat] Status da Carga {$carga->id} atualizada para: {$novoStatusCarga}");
                } else {
                    $carga->update($dadosAtualizacao); // Apenas salva o raw
                }
            }

            DB::commit();

            // 4. RETORNO RÁPIDO PARA A GR
            return response()->json(['status' => 'Laudo processado e integrado com sucesso.'], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::critical('Falha atômica ao processar Webhook TransSat', [
                'referencia' => $referencia,
                'error' => $e->getMessage()
            ]);
            
            // Retorna 500 para forçar a GR a tentar novamente mais tarde
            return response()->json(['error' => 'Falha interna na persistência do laudo.'], 500);
        }
    }

    private function mapearCodigoGrParaStatusMotorista(int $codigo): string
    {
        return match ($codigo) {
            1 => self::STATUS_GR_APROVADO,
            7 => self::STATUS_GR_AGUARDANDO_BIOMETRIA,
            2, 3, 5 => self::STATUS_GR_REJEITADO,
            default => 'pendente' // Fallback defensivo
        };
    }

    private function mapearCodigoGrParaStatusCarga(int $codigo): string
    {
        return match ($codigo) {
            1 => 'alocada',
            2, 3 => 'publicada',
            5 => 'pendente_correcao_gr',
            7 => 'aguardando_biometria',
            default => 'publicada'
        };
    }
}