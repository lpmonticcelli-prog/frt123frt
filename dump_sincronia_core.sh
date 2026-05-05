#!/bin/bash

# ==============================================================================
# [123fretei] CORE SYNCHRONY EXTRACTOR (LEVE E CIRÚRGICO)
# Extrai estritamente: Models, Controllers, Rotas e Frontend (Vue/JS)
# ==============================================================================

OUTPUT_FILE="123fretei_core_sincronia.txt"

echo "⚙️ Iniciando extração cirúrgica do Core Business..."

> "$OUTPUT_FILE"

echo "=========================================================================" >> "$OUTPUT_FILE"
echo " ÁRVORE DE DIRETÓRIOS DO CORE" >> "$OUTPUT_FILE"
echo "=========================================================================" >> "$OUTPUT_FILE"

# Mapeia apenas as pastas cruciais
tree app/Http/Controllers app/Models routes resources/js database/migrations -F >> "$OUTPUT_FILE" 2>/dev/null

echo -e "\n\n=========================================================================" >> "$OUTPUT_FILE"
echo " CÓDIGO FONTE (ESPINHA DORSAL)" >> "$OUTPUT_FILE"
echo "=========================================================================" >> "$OUTPUT_FILE"

# Busca apenas arquivos de código (.php, .vue, .js) dentro de pastas específicas
find app/Http/Controllers app/Models routes resources/js database/migrations \
    -type f \
    \( -name "*.php" -o -name "*.vue" -o -name "*.js" \) \
    -not -name "*.min.js" \
    -not -path "*/node_modules/*" \
    -not -path "*/vendor/*" \
    -exec bash -c '
        for file; do
            echo -e "\n\n// ========================================================================" >> "$1"
            echo "// 📂 ARQUIVO: $file" >> "$1"
            echo "// ========================================================================" >> "$1"
            cat "$file" >> "$1"
        done
    ' _ "$OUTPUT_FILE" {} +

echo "✅ DUMP CIRÚRGICO CONCLUÍDO. ARTEFATO GERADO: $OUTPUT_FILE"
