<?php

namespace App\DTOs;

readonly class CiotResponseDTO
{
    public function __construct(
        public bool $sucesso,
        public ?string $codigoCiot,
        public float $bruto,
        public float $inss,
        public float $sestSenat,
        public float $irrf,
        public float $valePedagio,
        public float $taxa123fretei,
        public float $liquidoMotorista,
        public array $payloadOriginal = []
    ) {}
}