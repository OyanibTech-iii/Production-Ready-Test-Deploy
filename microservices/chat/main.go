package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"sync"

	"github.com/gorilla/websocket"
	"github.com/redis/go-redis/v9"
)

var upgrader = websocket.Upgrader{
	ReadBufferSize:  1024,
	WriteBufferSize: 1024,
	CheckOrigin: func(r *http.Request) bool {
		return true // In production, validate origin
	},
}

type Message struct {
	ChatroomID  int    `json:"chatroom_id"`
	SenderID    int    `json:"sender_id"`
	SenderName  string `json:"sender_name"`
	SenderImage string `json:"sender_image"`
	Content     string `json:"content"`
	Type        string `json:"type"` // "message", "join", or "typing"
}

type Client struct {
	ID         int
	ChatroomID int
	Conn       *websocket.Conn
	Send       chan Message
}

type Hub struct {
	// Registered clients by ChatroomID
	Rooms      map[int]map[*Client]bool
	Broadcast  chan Message
	Register   chan *Client
	Unregister chan *Client
	mu         sync.Mutex
	redis      *redis.Client
}

func newHub(rdb *redis.Client) *Hub {
	return &Hub{
		Rooms:      make(map[int]map[*Client]bool),
		Broadcast:  make(chan Message),
		Register:   make(chan *Client),
		Unregister: make(chan *Client),
		redis:      rdb,
	}
}

func (h *Hub) listenRedis() {
	ctx := context.Background()
	pubsub := h.redis.Subscribe(ctx, "chat")
	defer pubsub.Close()

	ch := pubsub.Channel()

	for msg := range ch {
		var chatMsg Message
		if err := json.Unmarshal([]byte(msg.Payload), &chatMsg); err != nil {
			log.Printf("Error unmarshaling redis message: %v", err)
			continue
		}
		h.Broadcast <- chatMsg
	}
}

func (h *Hub) run() {
	for {
		select {
		case client := <-h.Register:
			h.mu.Lock()
			if h.Rooms[client.ChatroomID] == nil {
				h.Rooms[client.ChatroomID] = make(map[*Client]bool)
			}
			h.Rooms[client.ChatroomID][client] = true
			h.mu.Unlock()
			fmt.Printf("User %d joined Chatroom %d\n", client.ID, client.ChatroomID)

		case client := <-h.Unregister:
			h.mu.Lock()
			if _, ok := h.Rooms[client.ChatroomID][client]; ok {
				delete(h.Rooms[client.ChatroomID], client)
				close(client.Send)
			}
			h.mu.Unlock()
			fmt.Printf("User %d left Chatroom %d\n", client.ID, client.ChatroomID)

		case message := <-h.Broadcast:
			h.mu.Lock()
			clients := h.Rooms[message.ChatroomID]
			for client := range clients {
				select {
				case client.Send <- message:
				default:
					close(client.Send)
					delete(h.Rooms[message.ChatroomID], client)
				}
			}
			h.mu.Unlock()
		}
	}
}

func (c *Client) readPump(hub *Hub) {
	defer func() {
		hub.Unregister <- c
		c.Conn.Close()
	}()
	for {
		_, p, err := c.Conn.ReadMessage()
		if err != nil {
			log.Printf("error: %v", err)
			break
		}
		var msg Message
		if err := json.Unmarshal(p, &msg); err != nil {
			log.Printf("unmarshal error: %v", err)
			continue
		}

		// Publish incoming message to Redis to broadcast it to all instances
		ctx := context.Background()
		if err := hub.redis.Publish(ctx, "chat", string(p)).Err(); err != nil {
			log.Printf("redis publish error: %v", err)
		}

		log.Printf("Received and published message from WS: %+v", msg)
	}
}

func (c *Client) writePump() {
	defer func() {
		c.Conn.Close()
	}()
	for {
		message, ok := <-c.Send
		if !ok {
			c.Conn.WriteMessage(websocket.CloseMessage, []byte{})
			return
		}
		c.Conn.WriteJSON(message)
	}
}

func serveWs(hub *Hub, w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Println(err)
		return
	}

	userID := 0
	roomID := 0
	fmt.Sscanf(r.URL.Query().Get("user_id"), "%d", &userID)
	fmt.Sscanf(r.URL.Query().Get("room_id"), "%d", &roomID)

	client := &Client{ID: userID, ChatroomID: roomID, Conn: conn, Send: make(chan Message, 256)}
	hub.Register <- client

	go client.writePump()
	go client.readPump(hub)
}

func main() {
	redisAddr := os.Getenv("REDIS_ADDR")
	if redisAddr == "" {
		redisAddr = "localhost:6379"
	}

	rdb := redis.NewClient(&redis.Options{
		Addr: redisAddr,
	})

	hub := newHub(rdb)
	go hub.run()
	go hub.listenRedis()

	notificationHub := NewNotificationHub(rdb)
	go notificationHub.Run()
	go notificationHub.ListenRedis()

	http.HandleFunc("/ws", func(w http.ResponseWriter, r *http.Request) {
		serveWs(hub, w, r)
	})

	http.HandleFunc("/notifications-ws", func(w http.ResponseWriter, r *http.Request) {
		ServeNotificationsWs(notificationHub, w, r)
	})

	fmt.Printf("Chat microservice running on :8080 (Redis: %s)\n", redisAddr)
	log.Fatal(http.ListenAndServe(":8080", nil))
}
