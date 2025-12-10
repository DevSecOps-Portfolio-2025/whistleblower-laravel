<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Application\UseCases;

use Illuminate\Support\Facades\Event;
use Ramsey\Uuid\Uuid;
use Src\Whistleblowing\Domain\Entities\Report;
use Src\Whistleblowing\Domain\Events\ReportCreated;
use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;
use Src\Whistleblowing\Application\DTOs\CreateReportResponseDTO;

/**
 * Use Case: CreateReportUseCase
 * 
 * Caso de uso de aplicación para crear un nuevo reporte.
 * Orquesta la lógica de negocio y coordina con el repositorio.
 * 
 * IMPORTANTE: Genera y retorna el AccessCode en texto plano (única vez).
 */
class CreateReportUseCase
{
    private ReportRepositoryInterface $reportRepository;

    public function __construct(ReportRepositoryInterface $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * Ejecutar el caso de uso
     * 
     * @param array $data Datos del reporte: ['title', 'description', 'reporterId', 'actorIp']
     * @return CreateReportResponseDTO
     */
    public function execute(array $data): CreateReportResponseDTO
    {
        // Validaciones de negocio
        $this->validateData($data);

        // Crear la entidad de dominio con AccessCode generado automáticamente
        $report = Report::create(
            id: $this->generateId(),
            title: $data['title'],
            description: $data['description'],
            reporterId: $data['reporterId'] ?? null
        );

        // Persistir a través del repositorio
        $this->reportRepository->save($report);

        // Disparar evento de dominio para auditoría
        Event::dispatch(new ReportCreated($report, $data['actorIp'] ?? null));

        // Retornar DTO con el AccessCode en texto plano
        return new CreateReportResponseDTO(
            reportId: $report->getId(),
            accessCode: $report->getAccessCode()->value(), // ⚠️ Texto plano, única vez
            title: $report->getTitle(),
            status: $report->getStatus(),
            isAnonymous: $report->isAnonymous(),
            createdAt: $report->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    private function validateData(array $data): void
    {
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('El título es requerido');
        }

        if (empty($data['description'])) {
            throw new \InvalidArgumentException('La descripción es requerida');
        }

        if (strlen($data['title']) < 5) {
            throw new \InvalidArgumentException('El título debe tener al menos 5 caracteres');
        }

        if (strlen($data['description']) < 20) {
            throw new \InvalidArgumentException('La descripción debe tener al menos 20 caracteres');
        }
    }

    private function generateId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
