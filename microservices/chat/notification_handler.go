package main

import (
	"context"
	"fmt"
	"log"
	"net/http"
	"sync"

	"github.com/gorilla/websocket"
	"github.com/redis/go-redis/v9"
)

type NotificationHub struct {
	Clients    map[*websocket.Conn]bool
	Broadcast  chan []byte
	Register   chan *websocket.Conn
	Unregister chan *websocket.Conn
	mu         sync.Mutex
	redis      *redis.Client
}

func NewNotificationHub(rdb *redis.Client) *NotificationHub {
	return &NotificationHub{
		Clients:    make(map[*websocket.Conn]bool),
		Broadcast:  make(chan []byte),
		Register:   make(chan *websocket.Conn),
		Unregister: make(chan *websocket.Conn),
		redis:      rdb,
	}
}

func (h *NotificationHub) ListenRedis() {
	ctx := context.Background()
	pubsub := h.redis.Subscribe(ctx, "notifications")
	defer pubsub.Close()

	ch := pubsub.Channel()
	for msg := range ch {
		h.Broadcast <- []byte(msg.Payload)
	}
}

func (h *NotificationHub) Run() {
	for {
		select {
		case conn := <-h.Register:
			h.mu.Lock()
			h.Clients[conn] = true
			h.mu.Unlock()
			fmt.Println("Notification client registered")

		case conn := <-h.Unregister:
			h.mu.Lock()
			if _, ok := h.Clients[conn]; ok {
				delete(h.Clients, conn)
				conn.Close()
			}
			h.mu.Unlock()
			fmt.Println("Notification client unregistered")

		case message := <-h.Broadcast:
			h.mu.Lock()
			for conn := range h.Clients {
				err := conn.WriteMessage(websocket.TextMessage, message)
				if err != nil {
					log.Printf("Notification write error: %v", err)
					conn.Close()
					delete(h.Clients, conn)
				}
			}
			h.mu.Unlock()
		}
	}
}

func ServeNotificationsWs(hub *NotificationHub, w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Println(err)
		return
	}
	hub.Register <- conn
}

// Notification structure for JSON responses
type NotificationEvent struct {
	Count int    `json:"count"`
	Type  string `json:"type"` // "badge_update" or "new_notification"
}
