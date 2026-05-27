<?php

namespace App\Contracts;

interface RiskManagementInterface
{
    public function extrairDadosOcr(string $tipoDocumento, string $conteudoArquivo, string $mimeType): array;
    public function consultarRisco(array $dadosMotorista, array $dadosVeiculos = []): array;
}