<?php

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ciot;
use App\Contracts\PefGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\ProcessarAceiteCarga;

class CargaController extends Controller
{
    public function disponiveis(Request $request)
    {
        return response()->json([
            'status' => 'success',
            // CIRURGIA APLICADA: Sincronizado para "publicada"
            'data' => Carga::where('status', 'publicada')->orderBy('created_at', 'desc')->paginate(20)
        ], 200);
    }

    public function minhasCargas(Request $request)
    {
        $motoristaId = $request->user()->motorista->id ?? null;
        if (!$motoristaId) return response()->json(['error' => 'Perfil de motorista não localizado.'], 403);

        return response()->json([
            'status' => 'success',
            'data' => Carga::with(['embarcador', 'aceite_log', 'publicacao_log'])->where('motorista_id', $motoristaId)->orderBy('created_at', 'desc')->paginate(15)
        ], 200);
    }

    public function aceitar(Request $request, $id, PefGatewayInterface $pefGateway)
    {
        $user = $request->user();
        if (!$user->motorista) return response()->json(['error' => 'Acesso negado. Crie seu perfil.'], 403);

        // CIRURGIA APLICADA: Otimização brutal de Banco de Dados. Impede Deadlock com a API.
        $carga = DB::transaction(function () use ($id, $user) {
            $carga = Carga::lockForUpdate()->findOrFail($id);

            if ($carga->status !== 'publicada') {
                abort(409, 'Carga indisponível ou assumida por outro motorista.');
            }

            $carga->update(['status' => 'processando_aceite', 'motorista_id' => $user->motorista->id]);
            return $carga;
        });

        try {
            // CIRURGIA APLICADA: Chave UUID gerada corretamente antes do CIOT
            $idempotencyKey = (string) Str::uuid();

            // Chamada de API Externa isolada e Segura
            $respostaPef = $pefGateway->emitirCiot($carga);
            
            if (!$respostaPef->sucesso) {
                $carga->update(['status' => 'publicada', 'motorista_id' => null]);
                abort(422, 'Erro na emissão do CIOT: ' . $respostaPef->mensagemErro);
            }

            DB::transaction(function () use ($carga, $user, $respostaPef, $idempotencyKey, $request) {
                Ciot::create([
                    'idempotency_key' => $idempotencyKey,
                    'carga_id' => $carga->id,
                    'embarcador_id' => $carga->embarcador_id,
                    'motorista_id' => $user->motorista->id,
                    'codigo_ciot' => $respostaPef->codigoCiot,
                    'status' => 'processando', // Nasce como processando para engatilhar o Webhook
                    'valor_frete_bruto' => $respostaPef->bruto,
                    'imposto_inss' => $respostaPef->inss,
                    'imposto_sest_senat' => $respostaPef->sestSenat,
                    'imposto_irrf' => $respostaPef->irrf,
                    'valor_vale_pedagio' => $respostaPef->valePedagio,
                    'taxa_123fretei' => $respostaPef->taxa123fretei,
                    'valor_frete_liquido' => $respostaPef->liquidoMotorista,
                    'pef_payload_response' => $respostaPef->payloadOriginal,
                ]);

                ProcessarAceiteCarga::dispatch($carga->id, $user->id, $request->ip(), $request->userAgent());
            });

            return response()->json(['message' => 'Frete assinado. CIOT solicitado. Aguardando processamento da ANTT.'], 200);

        } catch (\Exception $e) {
            $carga->update(['status' => 'publicada', 'motorista_id' => null]);
            Log::error('[CRÍTICO] Falha no aceite da carga ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Falha de comunicação com o sistema ANTT.'], 500);
        }
    }

    public function cancelarAceite(Request $request, $id, PefGatewayInterface $pefGateway)
    {
        DB::transaction(function () use ($request, $id, $pefGateway) {
            $carga = Carga::lockForUpdate()->findOrFail($id);

            if ($carga->motorista_id !== $request->user()->motorista->id) abort(403, 'Operação negada.');
            if (!in_array($carga->status, ['aguardando_coleta', 'processando_aceite'])) abort(400, 'Status inválido para cancelamento.');

            $ciot = Ciot::where('carga_id', $carga->id)->first();
            if ($ciot) {
                $pefGateway->cancelarCiot($ciot->codigo_ciot);
                $ciot->update(['status' => 'cancelado']);
                $ciot->delete();
            }

            $carga->update(['status' => 'publicada', 'motorista_id' => null]);
        });

        return response()->json(['message' => 'Aceite cancelado. Carga devolvida ao mural.'], 200);
    }

    public function iniciarViagem(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $carga = Carga::lockForUpdate()->findOrFail($id);
            if ($carga->motorista_id !== $request->user()->motorista->id) abort(403, 'Operação negada.');
            if ($carga->status !== 'aguardando_coleta') abort(400, 'Status inválido. Aguarde a liberação do CIOT.');

            $carga->update(['status' => 'em_transito']);
        });

        return response()->json(['message' => 'Viagem iniciada.'], 200);
    }

    public function finalizarEntrega(Request $request, $id)
    {
        $request->validate([
            's3_path_canhoto' => 'required|string',
            's3_path_carga'   => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $carga = Carga::lockForUpdate()->findOrFail($id);
            if ($carga->motorista_id !== $request->user()->motorista->id) abort(403, 'Operação negada.');
            if ($carga->status !== 'em_transito') abort(400, 'Status inválido.');

            $carga->update([
                'status' => 'em_auditoria',
                'foto_canhoto' => $request->s3_path_canhoto,
                'foto_carga' => $request->s3_path_carga
            ]);
        });

        return response()->json(['message' => 'Entrega finalizada. Aguardando auditoria da Indústria.'], 200);
    }
}