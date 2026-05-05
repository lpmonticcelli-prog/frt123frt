<?php

$root = __DIR__;
$outputFile = $root . DIRECTORY_SEPARATOR . 'estrutura_123fretei.txt';

$ignoredDirectories = [
    'vendor',
    'node_modules',
    '.git',
    'storage',
    'bootstrap' . DIRECTORY_SEPARATOR . 'cache',
    'public' . DIRECTORY_SEPARATOR . 'build'
];

function scanDirectory($dir, $ignored, $prefix = '') {
    $output = '';
    $items = scandir($dir);
    $items = array_diff($items, ['.', '..']);
    
    usort($items, function($a, $b) use ($dir) {
        $aIsDir = is_dir($dir . DIRECTORY_SEPARATOR . $a);
        $bIsDir = is_dir($dir . DIRECTORY_SEPARATOR . $b);
        if ($aIsDir == $bIsDir) return strcasecmp($a, $b);
        return $aIsDir ? -1 : 1;
    });

    $count = count($items);
    $i = 0;

    foreach ($items as $item) {
        $i++;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $isLast = ($i == $count);
        $marker = $isLast ? '└── ' : '├── ';
        $childPrefix = $prefix . ($isLast ? '    ' : '│   ');

        if (is_dir($path)) {
            if (in_array($item, $ignored)) {
                $output .= $prefix . $marker . $item . "/ [Ignorado]\n";
                continue;
            }
            $output .= $prefix . $marker . $item . "/\n";
            $output .= scanDirectory($path, $ignored, $childPrefix);
        } else {
            // Ignora o próprio script de mapeamento
            if ($item === 'map.php' || $item === 'estrutura_123fretei.txt') {
                continue;
            }
            $output .= $prefix . $marker . $item . "\n";
        }
    }

    return $output;
}

$tree = "Topologia do Projeto 123fretei\n";
$tree .= "==============================\n\n";
$tree .= scanDirectory($root, $ignoredDirectories);

file_put_contents($outputFile, $tree);

echo "Mapeamento concluido com sucesso. Arquivo gerado: estrutura_123fretei.txt\n";