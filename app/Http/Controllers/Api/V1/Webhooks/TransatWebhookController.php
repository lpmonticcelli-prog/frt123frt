<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Carga;
use Illuminate\Support\Facades\Log;

class TransatWebhookController extends Controller
{
    public function handleCallback(Request $request)
    {
        // 1. BLINDAGEM ZERO-TRUST
        $tokenEsperado = config('services.transat.webhook_secret');
        
        $bearer = $request->header('Authorization') ?? $request->header('authorization');
        $tokenRecebido = str_replace(['Bearer ', 'bearer '], '', $bearer);

        if (empty($tokenRecebido) || !hash_equals($tokenEsperado, $tokenRecebido)) {
            Log::alert('[WEBHOOK HACK] Tentativa de forjar Laudo GR bloqueada.', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Acesso Negado'], 401);
        }

        // 2. RECUPERAÇÃO DA REFERÊNCIA
        $referencia = $request->input('referencia');
        if (!$referencia) return response()->json(['error' => 'Referencia vazia'], 400);

        $carga = Carga::where('gr_referencia', $referencia)->first();
        if (!$carga) return response()->json(['status' => 'Ignorado'], 200);

        // Salva Laudo Bruto no Banco
        $carga->gr_laudo_raw = $request->all();

        // 3. MÁQUINA DE ESTADO LOGÍSTICA
        $linhas = $request->input('lines', []);
        $codigoFinal = count($linhas) > 0 ? (int) $linhas[0]['codigo'] : (int) $request->input('codigo');

        switch ($codigoFinal) {
            case 1: // ACORDO (Limpo e Aprovado ✅)
                $carga->status = 'alocada';
                break;

            case 2: // DESACORDO (Roubo, Apreensão, Restrição Criminal ❌)
            case 3: // REQUER ATENÇÃO
                $carga->status = 'publicada'; // Devolve o frete pro Mural
                $carga->motorista_id = null; // Remove o motorista da operação
                $carga->gr_referencia = null;
                break;

            case 5: // ERRO DE PARÂMETRO (O OCR leu incorreto ou a placa não bate)
                $carga->status = 'pendente_correcao_gr';
                break;
                
            case 7: // AGUARDANDO FACIAL
                $carga->status = 'aguardando_biometria';
                // O Vue.js vai ler isso e exibir o botão para a biometria facial
                break;
        }

        $carga->save();

        // 4. SEMPRE RETORNE HTTP 200 RAPIDAMENTE (Evita timeout no lado da GR)
        return response()->json(['status' => 'Laudo Processado'], 200);
    }
}