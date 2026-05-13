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

class CargaController extends Controller
{
    public function disponiveis(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => Carga::with(['embarcador.user:id,name,email'])
                ->where('status', 'publicada')
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        ], 200);
    }

    public function minhasCargas(Request $request)
    {
        $motoristaId = $request->user()->motorista->id ?? null;
        if (!$motoristaId) return response()->json(['error' => 'Perfil de motorista não localizado.'], 403);

        return response()->json([
            'status' => 'success',
            'data' => Carga::with(['embarcador', 'aceite_log', 'publicacao_log'])
                ->where('motorista_id', $motoristaId)
                ->orderBy('created_at', 'desc')
                ->paginate(15)
        ], 200);
    }

    public function aceitar(Request $request, $id, PefGatewayInterface $pefGateway)
    {
        $user = $request->user();
        if (!$user->motorista) return response()->json(['error' => 'Acesso negado. Crie seu perfil.'], 403);

        // 1. Trava da Carga (Pessimistic Locking) para evitar aceite duplo
        $carga = DB::transaction(function () use ($id, $user) {
            $carga = Carga::lockForUpdate()->findOrFail($id);

            if ($carga->status !== 'publicada') {
                abort(409, 'Carga indisponível ou assumida por outro motorista.');
            }

            $carga->update(['status' => 'processando_aceite', 'motorista_id' => $user->motorista->id]);
            return $carga;
        });

        try {
            $idempotencyKey = (string) Str::uuid();

            // 2. Chamada ao PEF Gateway (No lab, usa o Mock)
            $respostaPef = $pefGateway->emitirCiot($carga);
            
            if (!$respostaPef->sucesso) {
                $carga->update(['status' => 'publicada', 'motorista_id' => null]);
                abort(422, 'Erro na emissão do CIOT: ' . $respostaPef->mensagemErro);
            }

            // 3. Consolidação (Cria o CIOT, o Termo Jurídico e Avança o Status)
            DB::transaction(function () use ($carga, $user, $respostaPef, $idempotencyKey, $request) {
                
                Ciot::create([
                    'idempotency_key' => $idempotencyKey,
                    'carga_id' => $carga->id,
                    'embarcador_id' => $carga->embarcador_id,
                    'motorista_id' => $user->motorista->id,
                    'codigo_ciot' => $respostaPef->codigoCiot,
                    // CIRURGIA: Forçamos o status para 'emitido' para simular o Webhook automático
                    'status' => 'emitido', 
                    'valor_frete_bruto' => $respostaPef->bruto,
                    'imposto_inss' => $respostaPef->inss,
                    'imposto_sest_senat' => $respostaPef->sestSenat,
                    'imposto_irrf' => $respostaPef->irrf,
                    'valor_vale_pedagio' => $respostaPef->valePedagio,
                    'taxa_123fretei' => $respostaPef->taxa123fretei,
                    'valor_frete_liquido' => $respostaPef->liquidoMotorista,
                    'pef_payload_response' => $respostaPef->payloadOriginal,
                ]);

                // Gera o Termo Jurídico Assinado
                $valorFormatado = number_format($carga->valor_frete, 2, ',', '.');
                $termoContrato = "CONTRATO DE TRANSPORTE AUTÔNOMO DE CARGA. Pelo presente aceite eletrônico, o motorista {$user->name} aceita realizar o transporte da carga ID {$carga->id}, de {$carga->cidade_origem}/{$carga->uf_origem} para {$carga->cidade_destino}/{$carga->uf_destino} pelo valor de R$ {$valorFormatado}.";
                $termoHash = hash('sha256', $termoContrato);

                DB::table('carga_aceites_log')->insert([
                    'carga_id' => $carga->id,
                    'motorista_id' => $user->motorista->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? 'App Motorista', 0, 255),
                    'termo_hash' => $termoHash,
                    'aceito_em' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // CIRURGIA FINAL: Avança a carga diretamente, quebrando o ciclo de espera do Webhook
                $carga->update(['status' => 'aguardando_coleta']);
            });

            return response()->json(['message' => 'Frete assinado e CIOT validado. Viagem liberada para iniciar!'], 200);

        } catch (\Exception $e) {
            $carga->update(['status' => 'publicada', 'motorista_id' => null]);
            Log::error('[CRÍTICO] Falha no aceite da carga ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Falha de comunicação com a ANTT.'], 500);
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