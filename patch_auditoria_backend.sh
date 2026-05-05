#!/bin/bash

echo "[123fretei] Injetando Endpoints de Auditoria do Embarcador..."

mkdir -p app/Http/Controllers/Api/V1/Embarcador

cat << 'CONTROLLER' > app/Http/Controllers/Api/V1/Embarcador/AuditoriaController.php
<?php

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    /**
     * 1. Aprova o PoD e engatilha a Liquidação do Frete
     */
    public function aprovarPagamento(Carga $carga, Request $request)
    {
        $user = $request->user();

        // Zero Trust: A carga pertence a este embarcador e está esperando auditoria?
        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }
        if ($carga->status !== 'em_auditoria') {
            return response()->json(['message' => 'A carga não está na mesa de auditoria.'], 400);
        }

        DB::transaction(function () use ($carga) {
            $carga->update(['status' => 'concluida']); // Ou 'entregue' dependendo da sua modelagem

            // Envia o CIOT para liquidação em background (Webhook para a Pamcard/NDD)
            // LiquidarFreteJob::dispatch($carga->id)->onQueue('financeiro');
        });

        return response()->json(['message' => 'Auditoria aprovada com sucesso.']);
    }

    /**
     * 2. Reprova o PoD e trava o dinheiro (Abre Disputa no SAC)
     */
    public function abrirDisputa(Carga $carga, Request $request)
    {
        $user = $request->user();

        if ($carga->embarcador_id !== $user->embarcador->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }
        
        $request->validate(['motivo' => 'required|string|max:500']);

        DB::transaction(function () use ($carga, $request, $user) {
            $carga->update(['status' => 'em_disputa']);

            // Abre automaticamente um chamado Nível 2 para a Mesa de Operações (Admin)
            Ticket::create([
                'user_id' => $user->id,
                'carga_id' => $carga->id,
                'categoria' => 'Disputa de Frete',
                'assunto' => 'Reprovação de PoD / Auditoria',
                'mensagem' => "O Embarcador abriu disputa após analisar o comprovante. Motivo alegado: " . $request->motivo,
                'status' => 'aberto',
                'prioridade' => 'alta'
            ]);
        });

        return response()->json(['message' => 'Disputa aberta. Operação congelada.']);
    }
}
CONTROLLER

echo "[123fretei] Controller de Auditoria criado."
