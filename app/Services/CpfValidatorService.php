<?php

namespace App\Services;

class CpfValidatorService
{
    /**
     * Valida um CPF utilizando o algoritmo matemático oficial da Receita Federal.
     *
     * @param string $cpf
     * @return bool
     */
    public function isValid(string $cpf): bool
    {
        // Extrai apenas os números
        $c = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($c) != 11) {
            return false;
        }

        // Verifica se foi informada uma sequência de dígitos repetidos (ex: 111.111.111-11)
        if (preg_match('/(\d)\1{10}/', $c)) {
            return false;
        }

        // Cálculo do primeiro dígito verificador
        for ($s = 10, $n = 0, $i = 0; $s >= 2; $n += $c[$i] * $s, $i++, $s--);
        if ($c[9] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
            return false;
        }

        // Cálculo do segundo dígito verificador
        for ($s = 11, $n = 0, $i = 0; $s >= 2; $n += $c[$i] * $s, $i++, $s--);
        if ($c[10] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
            return false;
        }

        return true;
    }
}