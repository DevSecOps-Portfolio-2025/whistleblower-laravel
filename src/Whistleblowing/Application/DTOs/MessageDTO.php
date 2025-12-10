<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Application\DTOs;

/**
 * DTO: MessageDTO
 * 
 * Data Transfer Object para representar un mensaje individual.
 */
final class MessageDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $content,
        public readonly string $author,
        public readonly string $createdAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'author' => $this->author,
            'created_at' => $this->createdAt,
        ];
    }
}
