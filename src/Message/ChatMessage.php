<?php

namespace App\Message;

class ChatMessage
{
    public const TYPE_FICOBOT = 'ficobot';
    public const TYPE_ADMIN = 'admin';

    public function __construct(
        private string $content,
        private string $type = self::TYPE_FICOBOT,
        private array $metadata = []
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
