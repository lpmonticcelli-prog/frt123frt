<?php
// Extrator de Código Limpo 123fretei
$nomeArquivo = '123fretei_codigo_limpo.txt';
$data = date('d/m/Y H:i:s');

echo "⏳ Iniciando a extração do código...\n";

// O pulo do gato: pega apenas os arquivos que o Git rastreia
exec('git ls-files', $arquivos);

$extensoes_permitidas = ['php', 'vue', 'js', 'ts', 'go'];
$contador = 0;

$saida = fopen($nomeArquivo, 'w');
fwrite($saida, "=== DUMP DE CÓDIGO FONTE 123FRETEI ===\nGerado em: {$data}\n\n");

foreach ($arquivos as $arquivo) {
    $ext = pathinfo($arquivo, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), $extensoes_permitidas)) {
        
        // Proteção extra: ignora arquivos compilados do Vite que o Git pode estar rastreando
        if (strpos($arquivo, 'public/build/') === 0 || strpos($arquivo, 'public/assets/') === 0) {
            continue;
        }

        $conteudo = file_get_contents($arquivo);
        
        $cabecalho  = "\n" . str_repeat('=', 80) . "\n";
        $cabecalho .= "📂 ARQUIVO: " . $arquivo . "\n";
        $cabecalho .= str_repeat('=', 80) . "\n\n";
        
        fwrite($saida, $cabecalho . $conteudo . "\n\n");
        $contador++;
    }
}

fclose($saida);
echo "✅ Sucesso absoluto! {$contador} arquivos do sistema foram unidos.\n";
echo "📦 O arquivo '{$nomeArquivo}' foi gerado na raiz do projeto.\n";