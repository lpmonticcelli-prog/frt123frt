package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"sync"

	"github.com/gorilla/websocket"
	"github.com/redis/go-redis/v9"
)

var ctx = context.Background()

// Conexão com o Redis (Aponta para o container do Docker)
var rdb = redis.NewClient(&redis.Options{
	Addr: "redis:6379", 
})

// Upgrader do WebSocket para aceitar conexões do Frontend (Vue.js)
var upgrader = websocket.Upgrader{
	CheckOrigin: func(r *http.Request) bool {
		return true // Em produção, restringir ao domínio da 123fretei
	},
}

// Estrutura de dados do GPS
type LocationData struct {
	DriverID int     `json:"driver_id"`
	CargaID  int     `json:"carga_id"`
	Lat      float64 `json:"lat"`
	Lng      float64 `json:"lng"`
}

// Gestor de conexões dos Embarcadores (quem está assistindo o mapa)
var shippers = make(map[*websocket.Conn]int) // Conn -> CargaID
var mutex = &sync.Mutex{}

func main() {
	// Endpoint para o Motorista enviar a localização
	http.HandleFunc("/ws/driver", handleDriverPing)
	
	// Endpoint para o Embarcador receber a localização
	http.HandleFunc("/ws/shipper", handleShipperWatch)

	fmt.Println("🚀 Microserviço de Rastreamento (Go) rodando na porta 8080...")
	log.Fatal(http.ListenAndServe(":8080", nil))
}

func handleDriverPing(w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Println("Erro no upgrade do Motorista:", err)
		return
	}
	defer conn.Close()

	for {
		_, msg, err := conn.ReadMessage()
		if err != nil {
			break
		}

		var loc LocationData
		if err := json.Unmarshal(msg, &loc); err != nil {
			continue
		}

		// 1. Salva a última localização no Redis (Comando Geoespacial)
		rdb.GeoAdd(ctx, "cargas_ativas", &redis.GeoLocation{
			Name:      fmt.Sprintf("%d", loc.DriverID),
			Longitude: loc.Lng,
			Latitude:  loc.Lat,
		})

		// 2. Publica a localização num canal específico da Carga (Pub/Sub)
		channelName := fmt.Sprintf("carga_tracking_%d", loc.CargaID)
		rdb.Publish(ctx, channelName, msg)
	}
}

func handleShipperWatch(w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Println("Erro no upgrade do Embarcador:", err)
		return
	}

	// Lê o CargaID que o embarcador quer vigiar (enviado na 1ª mensagem)
	_, msg, err := conn.ReadMessage()
	if err != nil {
		return
	}
	var initData struct {
		CargaID int `json:"carga_id"`
	}
	json.Unmarshal(msg, &initData)

	// Registra a conexão
	mutex.Lock()
	shippers[conn] = initData.CargaID
	mutex.Unlock()

	defer func() {
		mutex.Lock()
		delete(shippers, conn)
		mutex.Unlock()
		conn.Close()
	}()

	// Inscreve no canal do Redis para esta Carga específica
	channelName := fmt.Sprintf("carga_tracking_%d", initData.CargaID)
	pubsub := rdb.Subscribe(ctx, channelName)
	defer pubsub.Close()

	ch := pubsub.Channel()

	// Loop infinito escutando o Redis e enviando para o WebSocket do Vue.js
	for msg := range ch {
		mutex.Lock()
		err := conn.WriteMessage(websocket.TextMessage, []byte(msg.Payload))
		mutex.Unlock()
		if err != nil {
			break
		}
	}
}