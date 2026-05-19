package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"strings"
	"time"

	"github.com/gorilla/websocket"
	"github.com/redis/go-redis/v9"
)

var ctx = context.Background()

// Conexão Otimizada com o Redis (Pool de Conexões de Alta Concorrência)
var rdb = redis.NewClient(&redis.Options{
	Addr:         "redis:6379",
	PoolSize:     1000,               // Permite 1000 conexões simultâneas ao Redis
	MinIdleConns: 100,                // Mantém conexões quentes para evitar overhead de handshake TCP
	ReadTimeout:  3 * time.Second,
	WriteTimeout: 3 * time.Second,
})

// Upgrader blindado (Zero Trust)
var upgrader = websocket.Upgrader{
	ReadBufferSize:  1024,
	WriteBufferSize: 1024,
	CheckOrigin: func(r *http.Request) bool {
		// ALERTA DE COMPLIANCE: Em produção, substitua pelo domínio estrito (ex: "app.123fretei.com.br")
		origin := r.Header.Get("Origin")
		return strings.Contains(origin, "localhost") || strings.Contains(origin, "123fretei") || origin == ""
	},
}

// Payload Otimizado de Memória
type LocationData struct {
	DriverID  int     `json:"driver_id"`
	CargaID   int     `json:"carga_id"`
	Lat       float64 `json:"lat"`
	Lng       float64 `json:"lng"`
	Heading   float64 `json:"heading"`
	Timestamp int64   `json:"timestamp"`
}

func main() {
	// Ping de saúde (Healthcheck) para Orquestradores (Kubernetes/Docker Swarm)
	http.HandleFunc("/health", func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		w.Write([]byte("OK"))
	})

	// Endpoints da Malha de Telemetria
	http.HandleFunc("/ws/driver", handleDriverPing)
	http.HandleFunc("/ws/shipper", handleShipperWatch)

	log.Println("[TELEMETRIA] 🚀 Microsserviço de Alta Concorrência a operar na porta 8080...")
	log.Fatal(http.ListenAndServe(":8080", nil))
}

/**
 * MOTOR DE INGESTÃO (MOTORISTA): Recebe GPS, aplica Rate Limit e distribui (PubSub + Streams).
 */
func handleDriverPing(w http.ResponseWriter, r *http.Request) {
	// Validação Zero Trust: O Token deve ser validado antes de fazer o upgrade (Economia de RAM)
	token := r.URL.Query().Get("token")
	if token == "" {
		http.Error(w, "Unauthorized: Missing Token", http.StatusUnauthorized)
		return
	}
	// TODO: Integrar verificação de JWT ou validação no Redis (Auth Cache)

	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Println("[CRÍTICO] Falha no handshake do Driver:", err)
		return
	}
	defer conn.Close()

	// Rate Limiter Stateful por Conexão
	lastPingTime := time.Time{}

	for {
		_, msg, err := conn.ReadMessage()
		if err != nil {
			break // Sai do loop e liberta a Goroutine se o motorista perder rede
		}

		// Throttle: Bloqueia ataques DDoS ou GPS a flutuar loucamente (Drop de pings < 2 segundos)
		if time.Since(lastPingTime) < 2*time.Second {
			continue 
		}
		lastPingTime = time.Now()

		var loc LocationData
		if err := json.Unmarshal(msg, &loc); err != nil {
			continue // Ignora payload malformado
		}
		loc.Timestamp = time.Now().Unix()

		// Re-serialização otimizada
		payload, _ := json.Marshal(loc)
		channelName := fmt.Sprintf("carga_tracking_%d", loc.CargaID)

		// 1. LATÊNCIA SUB-MILISSEGUNDO: Pub/Sub para o Frontend do Embarcador (Tempo Real)
		rdb.Publish(ctx, channelName, payload)

		// 2. PERSISTÊNCIA DE DADOS (Event Sourcing): Grava no Stream para o Laravel consumir depois
		rdb.XAdd(ctx, &redis.XAddArgs{
			Stream: "gps_tracking_stream",
			MaxLen: 100000, // Limita a fila para não estourar a RAM do Redis (Drop old)
			Values: map[string]interface{}{
				"driver_id": loc.DriverID,
				"carga_id":  loc.CargaID,
				"lat":       loc.Lat,
				"lng":       loc.Lng,
				"heading":   loc.Heading,
				"timestamp": loc.Timestamp,
			},
		})
	}
}

/**
 * MOTOR DE BROADCAST (EMBARCADOR): Inscreve-se no canal do Redis e envia updates para o Client.
 */
func handleShipperWatch(w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Println("[CRÍTICO] Falha no handshake do Shipper:", err)
		return
	}
	defer conn.Close()

	// O embarcador deve enviar um JSON inicial informando qual carga quer monitorizar
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
	defer pubsub.Close() // MANDATÓRIO: Impede vazamento de conexões (Memory Leak) no Redis

	ch := pubsub.Channel()
	
	// Ticker para enviar Ping e evitar que load balancers/nginx cortem a ligação por inatividade
	ticker := time.NewTicker(45 * time.Second)
	defer ticker.Stop()

	clientGone := make(chan struct{})
	go func() {
		defer close(clientGone)
		for {
			if _, _, err := conn.ReadMessage(); err != nil {
				break
			}
		}
	}()

	// Loop Multiplexado de I/O (Bloqueia até que um evento ocorra)
	for {
		select {
		case <-clientGone:
			// Navegador fechado ou rede caiu. Matar Goroutine instantaneamente.
			return
		case <-ticker.C:
			// Ping Keep-Alive
			if err := conn.WriteMessage(websocket.PingMessage, nil); err != nil {
				return
			}
		case msg := <-ch:
			// Dados recebidos do Redis. Empurrar para o Socket do Embarcador.
			if err := conn.WriteMessage(websocket.TextMessage, []byte(msg.Payload)); err != nil {
				return
			}
		}
	}
}