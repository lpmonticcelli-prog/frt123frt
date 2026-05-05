#!/bin/bash
OUTPUT="codigo_auditoria.txt"
> $OUTPUT # Limpa o arquivo se já existir

ARQUIVOS=(
  "app/Http/Controllers/Api/V1/Embarcador/CargaController.php"
  "app/Models/Carga.php"
  "app/Http/Requests/StoreCargaRequest.php"
)

for file in "${ARQUIVOS[@]}"; do
  if [ -f "$file" ]; then
    echo "========================================" >> $OUTPUT
    echo "ARQUIVO: $file" >> $OUTPUT
    echo "========================================" >> $OUTPUT
    cat "$file" >> $OUTPUT
    echo -e "\n\n" >> $OUTPUT
  else
    echo "AVISO: $file não encontrado (Pode não ter sido criado ainda)."
  fi
done
