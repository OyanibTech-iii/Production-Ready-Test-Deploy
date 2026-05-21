<?php

namespace App\MessageHandler;

use App\Message\ChatMessage;
use App\Service\Chat\AdminChatService;
use App\Service\Chat\FicoBotService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ChatMessageHandler
{
    public function __construct(
        private FicoBotService $ficoBotService,
        private AdminChatService $adminChatService
    ) {
    }

    public function __invoke(ChatMessage $message): string
    {
        $response = match ($message->getType()) {
            ChatMessage::TYPE_FICOBOT => $this->ficoBotService->generateResponse($message->getContent()),
            ChatMessage::TYPE_ADMIN => $this->handleAdminMessage($message),
            default => null,
        };

        return $response ?? "I'm thinking...";
    }

    private function handleAdminMessage(ChatMessage $message): ?string
    {
        $metadata = $message->getMetadata();
        $chatroomId = $metadata['chatroom_id'] ?? null;
        $senderId = $metadata['sender_id'] ?? null;

        if ($chatroomId && $senderId) {
            $this->adminChatService->handleMessage(
                (int)$chatroomId,
                (int)$senderId,
                $message->getContent()
            );
            return 'Admin message processed.';
        }

        return null;
    }
}
