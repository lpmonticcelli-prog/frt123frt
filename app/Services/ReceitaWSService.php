<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReceitaWSService
{
    /**
     * Consulta o CNPJ na ReceitaWS e valida se está ATIVO.
     *
     * @param string $cnpj
     * @return array ['valido' => bool, 'mensagem' => string, 'dados' => array|null]
     */
    public function validarCNPJ(string $cnpj): array
    {
        // Remove qualquer máscara (pontos, barras, traços)
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpjLimpo) !== 14) {
            return ['valido' => false, 'mensagem' => 'O CNPJ informado não possui 14 dígitos.'];
        }

        try {
            // Timeout estrito de 10 segundos para não travar o processo (Worker/Request)
            $response = Http::timeout(10)->get("https://www.receitaws.com.br/v1/cnpj/{$cnpjLimpo}");

            // Tratamento do Gargalo Principal (Rate Limit de 3 requisições/minuto)
            if ($response->status() === 429) {
                Log::warning("ReceitaWS Rate Limit atingido para o CNPJ: {$cnpjLimpo}");
                return ['valido' => false, 'mensagem' => 'Sistema de validação do governo congestionado. Por favor, aguarde 1 minuto e tente salvar novamente.'];
            }

            // --- NOVO: Captura de CNPJ matematicamente inválido ou não encontrado ---
            if ($response->status() === 400 || $response->status() === 404) {
                return ['valido' => false, 'mensagem' => 'CNPJ inválido ou inexistente na base da Receita Federal.'];
            }

            if (!$response->successful()) {
                return ['valido' => false, 'mensagem' => 'Falha de comunicação com a Receita Federal.'];
            }

            $dados = $response->json();

            // A API ReceitaWS retorna status 'ERROR' para CNPJs que não existem no banco deles
            if (isset($dados['status']) && $dados['status'] === 'ERROR') {
                return ['valido' => false, 'mensagem' => 'CNPJ rejeitado: ' . ($dados['message'] ?? 'Documento inválido.')];
            }

            // Validação da Regra de Negócio Crítica: A empresa precisa estar operante
            if (isset($dados['situacao']) && $dados['situacao'] !== 'ATIVA') {
                return ['valido' => false, 'mensagem' => "Ação bloqueada. O CNPJ encontra-se com situação cadastral '{$dados['situacao']}' na Receita Federal."];
            }

            return [
                'valido' => true,
                'mensagem' => 'CNPJ válido e ativo.',
                'razao_social' => $dados['nome'] ?? null,
                'dados' => $dados
            ];

        } catch (\Exception $e) {
            Log::error("Erro na integração ReceitaWS: " . $e->getMessage());
            return ['valido' => false, 'mensagem' => 'Serviço de validação indisponível no momento. Tente novamente mais tarde.'];
        }
    }
}