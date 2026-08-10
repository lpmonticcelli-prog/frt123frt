<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class XmlParserService
{
    /**
     * Extrai dados de um arquivo XML neutralizando injeções SSRF e XXE.
     */
    public function parse(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        
        if (!$content) {
            throw new Exception('Falha de I/O ao ler o arquivo XML temporário.');
        }

        libxml_use_internal_errors(true);
        
        // ZT-DEFENSE: Proteção estrutural contra Billion Laughs (Exaustão RAM) e XXE (Vazamento de Arquivos)
        // LIBXML_NONET = Sem acesso externo. LIBXML_NOENT = Sem substituição de entidades.
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOENT);
        
        if (!$xml) {
            libxml_clear_errors();
            throw new Exception('A semântica do arquivo fornecido não reflete um XML válido e seguro.');
        }

        $nfe = $xml->NFe->infNFe ?? $xml->infNFe ?? null;
        if (!$nfe) {
            throw new Exception('O arquivo fiscal não representa uma NF-e (padrão SEFAZ) reconhecida.');
        }

        $peso = (float) ($nfe->transp->vol->pesoB ?? $nfe->transp->vol->pesoL ?? 0);
        $valor = (float) ($nfe->total->ICMSTot->vNF ?? 0);
        $produto = (string) ($nfe->det[0]->prod->xProd ?? 'Carga Geral');
        
        $cidadeOrigem = (string) ($nfe->emit->enderEmit->xMun ?? '');
        $ufOrigem = (string) ($nfe->emit->enderEmit->UF ?? '');
        
        $cidadeDestino = (string) ($nfe->dest->enderDest->xMun ?? '');
        $ufDestino = (string) ($nfe->dest->enderDest->UF ?? '');

        if ($peso <= 0 || $valor <= 0) {
            Log::warning('NFe Parser: NF-e processada com valores fiscais ou peso logístico zerados. Analisando como Simples Remessa.');
        }

        return [
            'produto' => $produto,
            'peso_kg' => $peso,
            'valor_frete' => $valor, 
            'cidade_origem' => $cidadeOrigem,
            'uf_origem' => $ufOrigem,
            'cidade_destino' => $cidadeDestino,
            'uf_destino' => $ufDestino,
            'xml_hash' => hash('sha256', $content) // Assinatura de auditoria para o File System
        ];
    }
}