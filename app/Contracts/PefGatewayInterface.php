<?php

namespace App\Contracts;

use App\Models\Carga;
use App\DTOs\CiotResponseDTO;

interface PefGatewayInterface
{
    /**
     * Emite o CIOT e processa a retenção de impostos comunicando com o gateway externo.
     * Retorna um DTO imutável para garantir a integridade da estrutura financeira.
     */
    public function emitirCiot(Carga $carga): CiotResponseDTO;

    /**
     * Cancela um CIOT previamente emitido.
     */
    public function cancelarCiot(string $codigoCiot): bool;

    /**
     * Confirma a liquidação (Pagamento final ao motorista após a entrega).
     */
    public function liquidarFrete(string $codigoCiot): bool;
}