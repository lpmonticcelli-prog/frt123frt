<?php

declare(strict_types=1);

// Motor de Extração Enterprise - Zero Trust / LLM Optimized
$data = date('d/m/Y H:i:s');
echo "⏳ Inicializando motor de extração sharded...\n";

// Captura apenas arquivos rastreados, garantindo a exclusão natural do vendor/ e node_modules/
exec('git ls-files', $arquivos);

$extensoes_permitidas = ['php', 'vue', 'js', 'ts', 'go'];

// ZT-DEFENSE: Blacklist rígida para preservar o Context Window e isolar Secrets
$diretorios_bloqueados = [
    'public/',
    'database/migrations/',
    'database/seeders/',
    'database/factories/',
    'storage/',
    'bootstrap/cache/',
    'config/', 
    'resources/css/'
];

$limite_bytes_por_arquivo = 2 * 1024 * 1024; // Sharding: 2MB máximo por bloco para evitar colapso de leitura
$bloco_atual = 1;
$bytes_escritos = 0;
$contador_arquivos = 0;

$nome_base = "123fretei_core_bloco_";
$saida = fopen($nome_base . $bloco_atual . '.txt', 'w');
fwrite($saida, "=== DUMP DE ARQUITETURA B2B (BLOCO {$bloco_atual}) ===\nGerado em: {$data}\n\n");

foreach ($arquivos as $arquivo) {
    $ext = pathinfo($arquivo, PATHINFO_EXTENSION);

    if (!in_array(strtolower($ext), $extensoes_permitidas)) {
        continue;
    }

    // Aplicação da Blacklist de Diretórios
    foreach ($diretorios_bloqueados as $dir) {
        if (strpos($arquivo, $dir) === 0) {
            continue 2;
        }
    }

    // Filtro Anti-Minification (L7 Defense Proxy)
    if (preg_match('/\.min\.(js|css)$/i', $arquivo) || strpos($arquivo, 'filament/app.js') !== false) {
        continue;
    }

    $conteudo = file_get_contents($arquivo);
    
    $cabecalho  = "\n" . str_repeat('=', 80) . "\n";
    $cabecalho .= "📂 ARQUIVO: " . $arquivo . "\n";
    $cabecalho .= str_repeat('=', 80) . "\n\n";

    $payload = $cabecalho . $conteudo . "\n\n";
    $tamanho_payload = strlen($payload);

    // Swap Atômico de Shard (Gera um novo arquivo ao atingir 2MB)
    if (($bytes_escritos + $tamanho_payload) > $limite_bytes_por_arquivo) {
        fclose($saida);
        $bloco_atual++;
        $bytes_escritos = 0;
        $saida = fopen($nome_base . $bloco_atual . '.txt', 'w');
        fwrite($saida, "=== DUMP DE ARQUITETURA B2B (BLOCO {$bloco_atual}) ===\nGerado em: {$data}\n\n");
    }

    fwrite($saida, $payload);
    $bytes_escritos += $tamanho_payload;
    $contador_arquivos++;
}

fclose($saida);
echo "✅ Operação concluída. {$contador_arquivos} arquivos de Core Processual divididos de forma segura em {$bloco_atual} blocos.\n";