#!/bin/bash
set -e

# Caminho absoluto mapeado do Windows para o WSL
TARGET_DIR="/mnt/c/Users/ADM/OneDrive/Área de Trabalho/123"

echo "[123fretei] Criando diretório de destino no Windows..."
mkdir -p "$TARGET_DIR"

echo "[123fretei] Limpando extrações anteriores..."
rm -f "$TARGET_DIR"/dump_123fretei_parte_*.txt

echo "[123fretei] Varrendo o sistema de arquivos..."

# Captura todos os arquivos ignorando pastas de compilação, logs e binários
mapfile -t FILES < <(find . -type f \
    -not -path "*/vendor/*" \
    -not -path "*/node_modules/*" \
    -not -path "*/.git/*" \
    -not -path "*/storage/*" \
    -not -path "*/public/build/*" \
    -not -name "*.jpg" -not -name "*.png" -not -name "*.pdf" -not -name "*.ico" -not -name "*.svg" \
    -not -name "*.lock" \
    | sort)

TOTAL_FILES=${#FILES[@]}

if [ "$TOTAL_FILES" -eq 0 ]; then
    echo "ERRO FATAL: Nenhum arquivo encontrado. Tem certeza que está no diretório ~/123fretei?"
    exit 1
fi

# Cálculo dinâmico para dividir o array de arquivos em exatas 8 partes
CHUNK_SIZE=$(( (TOTAL_FILES + 7) / 8 ))

echo "-> $TOTAL_FILES arquivos fonte detectados."
echo "-> Distribuindo em lotes de ~$CHUNK_SIZE arquivos por documento."

# =======================================================
# PARTE 1: INJETAR O MAPA DA ARQUITETURA NO PRIMEIRO ARQUIVO
# =======================================================
OUT_PART_1="$TARGET_DIR/dump_123fretei_parte_1.txt"
echo "==========================================================" > "$OUT_PART_1"
echo "TOPOLOGIA DE ARQUITETURA (123FRETEI)" >> "$OUT_PART_1"
echo "==========================================================" >> "$OUT_PART_1"

# Gera a árvore visual ignorando os diretórios pesados
find . -type d \
    -not -path "*/vendor*" \
    -not -path "*/node_modules*" \
    -not -path "*/.git*" \
    -not -path "*/storage*" \
    -not -path "*/public/build*" \
    | sort | sed -e 's/[^-][^\/]*\//  |/g' -e 's/|\([^ ]\)/|-\1/' >> "$OUT_PART_1"

echo -e "\n\n==========================================================" >> "$OUT_PART_1"
echo "CÓDIGO FONTE (INÍCIO)" >> "$OUT_PART_1"
echo "==========================================================\n" >> "$OUT_PART_1"

# =======================================================
# DIVISÃO DO CÓDIGO
# =======================================================
FILE_INDEX=0
CURRENT_PART=1

for FILE in "${FILES[@]}"; do
    
    CURRENT_OUT="$TARGET_DIR/dump_123fretei_parte_${CURRENT_PART}.txt"
    
    echo "==========================================================" >> "$CURRENT_OUT"
    echo "ARQUIVO: $FILE" >> "$CURRENT_OUT"
    echo "==========================================================" >> "$CURRENT_OUT"
    
    # Extrai o código fonte do arquivo atual
    cat "$FILE" >> "$CURRENT_OUT"
    echo -e "\n\n" >> "$CURRENT_OUT"

    FILE_INDEX=$((FILE_INDEX + 1))
    
    # Transição para o próximo arquivo quando atingir a cota (limitado a 8 partes)
    if [ $FILE_INDEX -ge $CHUNK_SIZE ] && [ $CURRENT_PART -lt 8 ]; then
        CURRENT_PART=$((CURRENT_PART + 1))
        FILE_INDEX=0
    fi
done

echo "[123fretei] Extração concluída com sucesso."
echo "-> Os 8 arquivos foram gerados em: C:\Users\ADM\OneDrive\Área de Trabalho\123"
