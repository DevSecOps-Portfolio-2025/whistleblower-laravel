<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Application\DTOs;

/**
 * DTO: ReportStatusDTO
 * 
 * Data Transfer Object para la respuesta de estado del reporte.
 * Incluye información del reporte y todos sus mensajes.
 */
final class ReportStatusDTO
{
    /**
     * @param MessageDTO[] $messages
     */
    public function __construct(
        public readonly string $reportId,
        public readonly string $title,
        public readonly string $description,
        public readonly string $status,
        public readonly bool $isAnonymous,
        public readonly array $messages,
        public readonly int $messageCount,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'is_anonymous' => $this->isAnonymous,
            'message_count' => $this->messageCount,
            'messages' => array_map(fn($msg) => $msg->toArray(), $this->messages),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
