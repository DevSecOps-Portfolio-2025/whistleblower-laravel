<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\Entities;

use DateTimeImmutable;
use Src\Whistleblowing\Domain\ValueObjects\AccessCode;

/**
 * Entity: Report
 * 
 * Entidad rica del dominio que representa un reporte de whistleblowing.
 * No depende de Eloquent, es un POJO (Plain Old PHP Object).
 * 
 * Soporta comunicación bidireccional mediante AccessCode y colección de mensajes.
 */
class Report
{
    private string $id;
    private string $title;
    private string $description;
    private string $status;
    private ?string $reporterId;
    private AccessCode $accessCode;
    private array $messages = [];
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $title,
        string $description,
        AccessCode $accessCode,
        string $status = 'pending',
        ?string $reporterId = null,
        array $messages = [],
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->accessCode = $accessCode;
        $this->status = $status;
        $this->reporterId = $reporterId;
        $this->messages = $messages;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    /**
     * Factory method para crear un nuevo Report con AccessCode generado
     */
    public static function create(
        string $id,
        string $title,
        string $description,
        ?string $reporterId = null
    ): self {
        return new self(
            $id,
            $title,
            $description,
            AccessCode::generate(),
            'pending',
            $reporterId
        );
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReporterId(): ?string
    {
        return $this->reporterId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getAccessCode(): AccessCode
    {
        return $this->accessCode;
    }

    /**
     * Obtener todos los mensajes del reporte
     * 
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Obtener el número de mensajes
     */
    public function getMessageCount(): int
    {
        return count($this->messages);
    }

    // Métodos de negocio
    public function markAsReviewed(): void
    {
        $this->status = 'reviewed';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsResolved(): void
    {
        $this->status = 'resolved';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isAnonymous(): bool
    {
        return $this->reporterId === null;
    }

    /**
     * Agregar un mensaje a la conversación
     */
    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Verificar si el AccessCode coincide
     */
    public function hasAccessCode(AccessCode $code): bool
    {
        return $this->accessCode->equalsSecure($code);
    }

    /**
     * Verificar si tiene mensajes
     */
    public function hasMessages(): bool
    {
        return count($this->messages) > 0;
    }

    /**
     * Obtener el último mensaje
     */
    public function getLastMessage(): ?Message
    {
        if (empty($this->messages)) {
            return null;
        }

        return end($this->messages);
    }
}
