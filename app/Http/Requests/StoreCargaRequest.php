<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

class StoreCargaRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     * Implementação da Arquitetura Zero Trust e Compliance.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // Só permite a requisição se for um embarcador com conta ativa.
        return $user !== null 
            && $user->role->slug === 'embarcador' 
            && $user->status === 'active';
    }

    /**
     * Intercepta a falha de autorização e devolve uma mensagem amigável 
     * e acionável para o frontend, em vez de um erro genérico 403.
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException(
            'Ação bloqueada. Sua conta não está ativa. Por favor, envie sua documentação empresarial no menu "Minha Conta" e aguarde a auditoria para publicar fretes.'
        );
    }

    /**
     * Regras de validação que serão aplicadas à requisição.
     */
    public function rules(): array
    {
        return [
            'produto' => 'required|string|max:255',
            // Alinhado com o frontend
            'especie' => 'required|string|in:caixas,paletes,sacaria,granel,tambores,outro',
            'peso_kg' => 'required|numeric|min:0.01',
            'cubagem_m3' => 'nullable|numeric|min:0.01',
            // Alinhado com o frontend (adicionado fiorino, carreta, vanderleia)
            'tipo_veiculo' => 'required|string|in:fiorino,toco,truck,bitruck,carreta,carreta_ls,vanderleia,bitrem',
            // Alinhado com o frontend ('aberta' no lugar de 'carga_seca')
            'tipo_carroceria' => 'required|string|in:bau,sider,aberta,graneleiro,frigorifico,prancha',
            'cidade_origem' => 'required|string|max:255',
            'uf_origem' => 'required|string|size:2',
            'cidade_destino' => 'required|string|max:255',
            'uf_destino' => 'required|string|size:2',
            'distancia_km' => 'nullable|numeric|min:1',
            'valor_frete' => 'required|numeric|min:1',
            'data_coleta' => 'required|date|after_or_equal:today',
            'data_entrega_prevista' => 'nullable|date|after_or_equal:data_coleta',
        ];
    }

    /**
     * Mensagens de erro personalizadas (opcional, melhora o feedback do frontend).
     */
    public function messages(): array
    {
        return [
            'tipo_veiculo.in' => 'O tipo de veículo selecionado é inválido.',
            'tipo_carroceria.in' => 'O tipo de carroceria selecionado é inválido.',
            'especie.in' => 'A espécie de embalagem selecionada é inválida.',
            'data_coleta.after_or_equal' => 'A data de coleta não pode estar no passado.',
            'data_entrega_prevista.after_or_equal' => 'A data de entrega não pode ser anterior à data de coleta.',
        ];
    }
}