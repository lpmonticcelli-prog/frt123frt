<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\Embarcador;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Exception;

class DarkRoomService
{
    /**
     * ZT-DEFENSE: PKI Vault Allocation (In-Memory Processing).
     * O certificado nunca é tocado de forma descriptografada fora da RAM, nem salvo na raiz pública.
     */
    public function trancarCertificado(Embarcador $embarcador, UploadedFile $certificado, string $senha): void
    {
        $conteudo = file_get_contents($certificado->getRealPath());
        $certs = [];

        if (!openssl_pkcs12_read($conteudo, $certs, $senha)) {
            throw new Exception('Acesso Negado: A senha (PIN) informada é inválida ou o Payload PKCS#12 foi adulterado.');
        }

        $certData = openssl_x509_parse($certs['cert']);
        if (!$certData) {
            throw new Exception('Acesso Negado: Quebra estrutural na cadeia de assinatura X.509.');
        }

        if (isset($certData['validTo_time_t']) && $certData['validTo_time_t'] < time()) {
            throw new Exception('Compliance: Certificado ICP-Brasil expirado. A emissão de documentos está bloqueada.');
        }

        $cnpjRaw = $this->extrairCnpj($certData);
        $cnpjFormatado = preg_replace('/[^0-9]/', '', $cnpjRaw);
        
        // Acesso seguro ao CNPJ da model (O Eloquent trata a descriptografia AES em tempo de execução)
        $cnpjNoBanco = preg_replace('/[^0-9]/', '', (string) $embarcador->cnpj); 
        
        if ($cnpjFormatado !== $cnpjNoBanco) {
            throw new Exception("WAF Alert (Falsidade Ideológica): O CNPJ acoplado ao certificado ({$cnpjFormatado}) diverge da matriz de faturamento logada ({$cnpjNoBanco}).");
        }

        $disk = Storage::disk('dark_room');
        $filename = hash('sha256', microtime(true) . $embarcador->id) . '.pfx';
        $path = "embarcadores/{$embarcador->id}/{$filename}";
        
        if (!$disk->put($path, $conteudo)) {
             throw new Exception('Falha Crítica de I/O na gravação do cluster isolado (Dark Room).');
        }
        
        $embarcador->certificado_a1_senha = $senha; 
        $embarcador->certificado_a1_path = $path;
        
        if (isset($certData['validTo_time_t'])) {
            $embarcador->certificado_a1_vencimento = date('Y-m-d H:i:s', $certData['validTo_time_t']);
        }
        
        $embarcador->save();
    }

    private function extrairCnpj(array $certData): string
    {
        $payload = json_encode([$certData['subject'] ?? [], $certData['extensions'] ?? []]);
        if (preg_match('/\b\d{14}\b|\b\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\b/', (string) $payload, $matches)) {
            return $matches[0];
        }
        throw new Exception('Corrupção ASN.1: Impossível localizar a OID de Cadastro Nacional no emissor (ICP-Brasil).');
    }
}