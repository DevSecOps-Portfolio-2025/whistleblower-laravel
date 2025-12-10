<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Application\DTOs;

/**
 * DTO: CreateReportResponseDTO
 * 
 * Data Transfer Object para la respuesta de creación de reporte.
 * Incluye el AccessCode en texto plano (solo se muestra una vez).
 */
final class CreateReportResponseDTO
{
    public function __construct(
        public readonly string $reportId,
        public readonly string $accessCode,
        public readonly string $title,
        public readonly string $status,
        public readonly bool $isAnonymous,
        public readonly string $createdAt
    ) {}

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'access_code' => $this->accessCode,
            'title' => $this->title,
            'status' => $this->status,
            'is_anonymous' => $this->isAnonymous,
            'created_at' => $this->createdAt,
            'message' => '⚠️ IMPORTANTE: Guarde este código de acceso. Es la única forma de consultar su reporte.',
        ];
    }
}
