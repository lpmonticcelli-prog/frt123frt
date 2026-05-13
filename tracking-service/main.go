package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"time"

	"github.com/gorilla/websocket"
	"github.com/redis/go-redis/v9"
)

var ctx = context.Background()

// Ligação com o Redis (Aponta para o container do Docker)
var rdb = redis.NewClient(&redis.Options{
	Addr: "redis:6379",
})

// Upgrader do WebSocket para aceitar ligações do Frontend (Vue.js)
var upgrader = websocket.Upgrader{
	CheckOrigin: func(r *http.Request) bool {
		return true // Em produção, aplicar restrição de domínio CORS (123fretei.com.br)
	},
}

// Estrutura de dados do GPS
type LocationData struct {
	DriverID int     `json:"driver_id"`
	CargaID  int     `json:"carga_id"`
	Lat      float64 `json:"lat"`
	Lng      float64 `json:"lng"`
	Heading  float64 `json:"heading"` // Rotação do vetor veicular
}

func main() {
	// Endpoints da Arquitetura
	http.HandleFunc("/ws/driver", handleDriverPing)
	http.HandleFunc("/ws/shipper", handleShipperWatch)

	log.Println("🚀 Microserviço de Rastreamento (GO) a operar na porta 8080...")
	log.Fatal(http.ListenAndServe(":8080", nil))
}

// O Motorista emite o sinal e grava na infraestrutura de fila rápida (Redis)
func handleDriverPing(w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Println("[CRÍTICO] Falha no handshake do Driver:", err)
		return
	}
	defer conn.Close()

	for {
		_, msg, err := conn.ReadMessage()
		if err != nil {
			break // Sai do loop e encerra a goroutine quando o sinal do motorista cai
		}

		var loc LocationData
		if err := json.Unmarshal(msg, &loc); err != nil {
			continue
		}

		// 1. Grava a coordenada exata com geolocalização nativa do Redis
		rdb.GeoAdd(ctx, "cargas_ativas", &redis.GeoLocation{
			Name:      fmt.Sprintf("%d", loc.DriverID),
			Longitude: loc.Lng,
			Latitude:  loc.Lat,
		})

		// 2. Transmite via Pub/Sub para que o Embarcador intercete o sinal
		channelName := fmt.Sprintf("carga_tracking_%d", loc.CargaID)
		rdb.Publish(ctx, channelName, msg)
	}
}

// O Embarcador consome o sinal num circuito hermético livre de Memory Leaks
func handleShipperWatch(w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Println("[CRÍTICO] Falha no handshake do Shipper:", err)
		return
	}
	defer conn.Close()

	// Interceta a carga subscrita no primeiro envio
	_, msg, err := conn.ReadMessage()
	if err != nil {
		return
	}

	var initData struct {
		CargaID int `json:"carga_id"`
	}
	if err := json.Unmarshal(msg, &initData); err != nil {
		return
	}

	channelName := fmt.Sprintf("carga_tracking_%d", initData.CargaID)
	pubsub := rdb.Subscribe(ctx, channelName)
	defer pubsub.Close() // Fecha o canal do Redis quando a função morre

	ch := pubsub.Channel()
	ticker := time.NewTicker(50 * time.Second) // Ping interval para estabilidade TCP
	defer ticker.Stop()

	// Canal local de controlo para detetar a morte prematura do cliente
	clientGone := make(chan struct{})
	go func() {
		defer close(clientGone)
		for {
			if _, _, err := conn.ReadMessage(); err != nil {
				break
			}
		}
	}()

	// Loop multiplexado anti-Memory Leak
	for {
		select {
		case <-clientGone:
			// O navegador do Embarcador foi fechado ou a rede caiu. Purga a memória de imediato.
			return
		case msg := <-ch:
			// Sinal recebido do Pub/Sub. Repassa ao Embarcador via WebSocket.
			if err := conn.WriteMessage(websocket.TextMessage, []byte(msg.Payload)); err != nil {
				return
			}
		case <-ticker.C:
			// Heartbeat para prevenir timeout dos load balancers (AWS ELB)
			if err := conn.WriteMessage(websocket.PingMessage, nil); err != nil {
				return
			}
		}
	}
}