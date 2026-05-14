<?php

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Carga;

class CarteiraController extends Controller
{
    public function extrato(Request $request)
    {
        $motorista = $request->user()->motorista;

        // Medida de segurança caso o perfil do motorista ainda esteja a ser criado
        if (!$motorista) {
            return response()->json([
                'saldo_disponivel' => 0,
                'transacoes' => []
            ]);
        }

        // Busca todas as cargas que o motorista já concluiu com sucesso
        $cargas = Carga::where('motorista_id', $motorista->id)
            ->whereIn('status', ['entregue', 'finalizada'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $transacoes = [];
        $saldoTotalGanhos = 0;

        foreach ($cargas as $carga) {
            // O valor ganho pelo motorista é o total do frete menos a taxa retida pela plataforma
            $valorLiquido = $carga->valor_frete - ($carga->taxa_plataforma ?? 0);
            $saldoTotalGanhos += $valorLiquido;

            // Formata no padrão que o seu Vue.js está a esperar
            $transacoes[] = [
                'id' => 'frete_' . $carga->id,
                'tipo' => 'credito',
                'descricao' => "Frete Concluído: {$carga->cidade_origem}/{$carga->uf_origem} ➔ {$carga->cidade_destino}/{$carga->uf_destino}",
                'valor' => $valorLiquido,
                'created_at' => $carga->updated_at,
            ];
        }

        return response()->json([
            'saldo_disponivel' => $saldoTotalGanhos,
            'transacoes' => $transacoes
        ]);
    }
}