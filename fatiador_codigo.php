<?php
// Robô Fatiador Cirúrgico 123fretei
$arquivoOrigem = '123fretei_codigo_limpo.txt';

if (!file_exists($arquivoOrigem)) {
    die("❌ Arquivo original não encontrado. Verifique o nome.\n");
}

$leitor = fopen($arquivoOrigem, "r");
$parte = 1;
$linhasPorArquivo = 8500; // Divide as 25 mil linhas em 3 blocos de ~8.5k
$contadorLinhas = 0;

$nomeArquivoDestino = "123fretei_bloco_{$parte}.txt";
$escritor = fopen($nomeArquivoDestino, "w");

echo "⏳ Iniciando o fatiamento Nível Deus...\n";

while (($linha = fgets($leitor)) !== false) {
    // Se o arquivo já atingiu o limite de linhas E a próxima linha é o início de um NOVO arquivo do sistema...
    if ($contadorLinhas >= $linhasPorArquivo && strpos($linha, '📂 ARQUIVO:') !== false) {
        // Tranca o arquivo atual
        fclose($escritor);
        echo "✅ {$nomeArquivoDestino} gerado com sucesso.\n";
        
        // Abre o próximo arquivo
        $parte++;
        $contadorLinhas = 0;
        $nomeArquivoDestino = "123fretei_bloco_{$parte}.txt";
        $escritor = fopen($nomeArquivoDestino, "w");
    }
    
    // Escreve a linha no arquivo atual
    fwrite($escritor, $linha);
    $contadorLinhas++;
}

fclose($leitor);
if ($escritor) {
    fclose($escritor);
    echo "✅ {$nomeArquivoDestino} gerado com sucesso.\n";
}

echo "🎯 Fatiamento concluído! O seu código foi dividido em {$parte} blocos perfeitos, sem quebrar nenhuma função ao meio.\n";