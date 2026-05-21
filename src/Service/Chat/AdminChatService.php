<?php

namespace App\Service\Chat;

use App\Entity\ChatMessage;
use App\Entity\Chatroom;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;

class AdminChatService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RedisClient $redis
    ) {
    }

    public function handleMessage(int $chatroomId, int $senderId, string $content): void
    {
        $chatroom = $this->entityManager->getRepository(Chatroom::class)->find($chatroomId);
        $sender = $this->entityManager->getRepository(User::class)->find($senderId);

        if (!$chatroom || !$sender) {
            return;
        }

        $message = new ChatMessage();
        $message->setChatroom($chatroom);
        $message->setSender($sender);
        $message->setContent($content);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Publish to Redis for real-time delivery via Go microservice
        $this->redis->publish('chat', json_encode([
            'chatroom_id' => $chatroomId,
            'sender_id' => $senderId,
            'sender_name' => $sender->getFirstName(),
            'sender_image' => $sender->getProfileImage(),
            'content' => $content,
            'type' => 'message'
        ]));
    }
}
