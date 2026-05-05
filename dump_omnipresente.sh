#!/bin/bash

# ==============================================================================
# [123fretei] OMNIPRESENT CODEBASE EXTRACTOR (FULL DUMP)
# Extração absoluta de 100% da aplicação (ignora apenas binários/mídias)
# ==============================================================================

OUTPUT_FILE="123fretei_dump_omnipresente.txt"

echo "☢️ INICIANDO DUMP ABSOLUTO DO SISTEMA (ISSO PODE LEVAR ALGUNS MINUTOS)..."

# Limpa/Cria o arquivo de saída
> "$OUTPUT_FILE"

echo "=========================================================================" >> "$OUTPUT_FILE"
echo " MAPA TOPOLÓGICO ABSOLUTO (DIRETÓRIOS E ARQUIVOS)" >> "$OUTPUT_FILE"
echo "=========================================================================" >> "$OUTPUT_FILE"

# Mapeia tudo, escondendo apenas a pasta do git para evitar lixo de versionamento
if command -v tree &> /dev/null; then
    tree -a -I ".git" -F >> "$OUTPUT_FILE"
else
    find . -not -path "*/\.git/*" | sort >> "$OUTPUT_FILE"
fi

echo -e "\n\n=========================================================================" >> "$OUTPUT_FILE"
echo " CÓDIGO FONTE INTEGRAL (INCLUI VENDOR, NODE_MODULES E CONFIGS)" >> "$OUTPUT_FILE"
echo "=========================================================================" >> "$OUTPUT_FILE"

# O comando 'find' varre todas as pastas. 
# Filtramos extensões binárias (mídia, fontes, compactados, executáveis e bancos SQLite)
find . -type f \
    -not -path "*/\.git/*" \
    -not -name "*.jpg" -not -name "*.jpeg" -not -name "*.png" -not -name "*.gif" -not -name "*.svg" -not -name "*.ico" -not -name "*.webp" \
    -not -name "*.mp4" -not -name "*.webm" -not -name "*.avi" -not -name "*.mov" \
    -not -name "*.mp3" -not -name "*.wav" \
    -not -name "*.zip" -not -name "*.tar.gz" -not -name "*.rar" -not -name "*.7z" \
    -not -name "*.pdf" -not -name "*.exe" -not -name "*.dll" -not -name "*.so" \
    -not -name "*.ttf" -not -name "*.woff" -not -name "*.woff2" -not -name "*.eot" \
    -not -name "*.sqlite" -not -name "*.db" \
    -exec bash -c '
        for file; do
            # Filtro militar: Verifica o "MimeType" do arquivo. Se for texto, ele extrai. Se for binário desconhecido, ele ignora.
            if file -i "$file" | grep -q "text/"; then
                echo -e "\n\n// ========================================================================" >> "$1"
                echo "// 📂 ARQUIVO: $file" >> "$1"
                echo "// ========================================================================" >> "$1"
                cat "$file" >> "$1"
            fi
        done
    ' _ "$OUTPUT_FILE" {} +

echo "✅ DUMP ABSOLUTO CONCLUÍDO. O ARTEFATO GIGANTE ESTÁ PRONTO: $OUTPUT_FILE"
