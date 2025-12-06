<?php

namespace Src\Whistleblowing\Domain\Entities;

use DateTimeImmutable;

/**
 * Entity: Report
 * 
 * Entidad rica del dominio que representa un reporte de whistleblowing.
 * No depende de Eloquent, es un POJO (Plain Old PHP Object).
 */
class Report
{
    private string $id;
    private string $title;
    private string $description;
    private string $status;
    private ?string $reporterId;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $title,
        string $description,
        string $status = 'pending',
        ?string $reporterId = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->reporterId = $reporterId;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
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
}
