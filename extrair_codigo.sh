#!/bin/bash

OUTPUT="123fretei_dump_arquitetura.txt"

echo "[123FRETEI] Iniciando extração. Isso pode levar alguns segundos..."
echo "GERADO EM: $(date)" > $OUTPUT
echo "" >> $OUTPUT

echo "================================================================" >> $OUTPUT
echo "1. TOPOLOGIA DO SISTEMA (ESTRUTURA DE PASTAS E ARQUIVOS)" >> $OUTPUT
echo "================================================================" >> $OUTPUT
# Imprime a árvore de arquivos ignorando os diretórios pesados
find . -type f -not -path "*/vendor/*" -not -path "*/node_modules/*" -not -path "*/.git/*" -not -path "*/storage/framework/*" -not -path "*/storage/logs/*" | sort >> $OUTPUT

echo -e "\n\n================================================================" >> $OUTPUT
echo "2. CÓDIGO FONTE CIRÚRGICO (PHP, VUE, JS)" >> $OUTPUT
echo "================================================================" >> $OUTPUT

# Varre, lê e concatena o conteúdo apenas das extensões vitais
find . -type f \( -name "*.php" -o -name "*.vue" -o -name "*.js" \) \
    -not -path "*/vendor/*" \
    -not -path "*/node_modules/*" \
    -not -path "*/.git/*" \
    -not -path "*/storage/framework/*" \
    -not -path "*/public/build/*" | sort | while read -r arquivo; do
    
    echo -e "\n\n/////////////////////////////////////////////////////////////////////////////////" >> $OUTPUT
    echo "// ARQUIVO: $arquivo" >> $OUTPUT
    echo "/////////////////////////////////////////////////////////////////////////////////" >> $OUTPUT
    cat "$arquivo" >> $OUTPUT
done

echo "[DIAGNÓSTICO] Extração concluída. Arquivo gerado: $OUTPUT"
