#!/bin/bash

# ==============================================================================
# [123fretei] RAW CODEBASE DUMP (OVERRIDE DE SEGURANÇA ATIVADO)
# Extração irrestrita de código, incluindo .env e chaves de produção.
# ==============================================================================

OUTPUT_FILE="123fretei_dump_absoluto.txt"

echo "⚠️ INICIANDO DUMP IRRESTRITO DA APLICAÇÃO..."

# Limpa o arquivo se já existir
> "$OUTPUT_FILE"

echo "=========================================================================" >> "$OUTPUT_FILE"
echo " ÁRVORE DE DIRETÓRIOS" >> "$OUTPUT_FILE"
echo "=========================================================================" >> "$OUTPUT_FILE"

# Lista a arquitetura completa (Ignora apenas a pasta oculta do git para evitar lixo binário)
if command -v tree &> /dev/null; then
    tree -a -I ".git|.idea|.vscode" -F >> "$OUTPUT_FILE"
else
    find . -not -path "*/\.git/*" -print | sort >> "$OUTPUT_FILE"
fi

echo -e "\n\n=========================================================================" >> "$OUTPUT_FILE"
echo " CÓDIGO FONTE (INCLUINDO ARQUIVOS OCULTOS E CHAVES)" >> "$OUTPUT_FILE"
echo "=========================================================================" >> "$OUTPUT_FILE"

# Varredura total: Puxa todos os arquivos de texto legíveis, ignorando apenas lixo de compilação e binários
find . -type f \
    -not -path "*/\.git/*" \
    -not -path "*/storage/framework/views/*" \
    -not -path "*/storage/logs/*" \
    -not -name "*.jpg" -not -name "*.png" -not -name "*.ico" -not -name "*.svg" -not -name "*.pdf" -not -name "*.lock" -not -name "*.sqlite" \
    -exec bash -c '
        for file; do
            # Filtro de segurança técnica: Garante que apenas arquivos de texto entrem no dump, evitando corromper o .txt com binários de fontes ou executáveis
            if file -i "$file" | grep -q "text/"; then
                echo -e "\n\n// ========================================================================" >> "$1"
                echo "// 📂 ARQUIVO: $file" >> "$1"
                echo "// ========================================================================" >> "$1"
                cat "$file" >> "$1"
            fi
        done
    ' _ "$OUTPUT_FILE" {} +

echo "✅ EXTRAÇÃO LETAL CONCLUÍDA. ARTEFATO GERADO: $OUTPUT_FILE"
