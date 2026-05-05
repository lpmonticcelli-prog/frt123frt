#!/bin/bash
echo "🚀 Iniciando ambiente FreteMatch..."
echo "🐳 Solicitando inicialização do Docker Desktop..."
cmd.exe /c "start \"\" \"C:\Program Files\Docker\Docker\Docker Desktop.exe\""
sleep 10
echo "🐘 Subindo infraestrutura Backend (Docker Compose)..."
docker compose up -d
echo "🐹 Subindo Servidor de Rastreamento (Go)..."
cd tracking-service && go run main.go &
PID_GO=$!
cd ..
echo "⚡ Subindo Frontend (Vue/Vite)..."
npm run dev -- --host &
PID_VITE=$!
echo "✅ Todos os serviços estão operacionais!"
echo "⚠️ Pressione [CTRL + C] nesta janela para derrubar todos os serviços."
trap "kill $PID_GO $PID_VITE; echo '🛑 Serviços encerrados.'" EXIT
wait
