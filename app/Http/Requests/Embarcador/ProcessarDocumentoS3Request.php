<?php

declare(strict_types=1);

namespace App\Http\Requests\Embarcador;

use Illuminate\Foundation\Http\FormRequest;

class ProcessarDocumentoS3Request extends FormRequest
{
    public function authorize(): bool
    {
        // Acesso já blindado pelo middleware ability:embarcador no router genérico.
        return true;
    }

    public function rules(): array
    {
        return [
            's3_path' => [
                'required',
                'string',
                'max:512',
                'regex:/^dfe\/embarcadores\/\d+\/[a-zA-Z0-9\-\_\.]+\.xml$/'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            's3_path.required' => 'O caminho do documento no repositório cloud é obrigatório.',
            's3_path.regex'    => 'O caminho fornecido viola o padrão arquitetural do cofre DFe.'
        ];
    }
}