<?php

namespace Src\Whistleblowing\Application\UseCases;

use Src\Whistleblowing\Domain\Entities\Report;
use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;

/**
 * Use Case: CreateReportUseCase
 * 
 * Caso de uso de aplicación para crear un nuevo reporte.
 * Orquesta la lógica de negocio y coordina con el repositorio.
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
     * @param array $data Datos del reporte: ['title', 'description', 'reporterId']
     * @return Report
     */
    public function execute(array $data): Report
    {
        // Validaciones de negocio
        $this->validateData($data);

        // Crear la entidad de dominio
        $report = new Report(
            id: $this->generateId(),
            title: $data['title'],
            description: $data['description'],
            status: 'pending',
            reporterId: $data['reporterId'] ?? null
        );

        // Persistir a través del repositorio
        $this->reportRepository->save($report);

        return $report;
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
        // En producción, usa UUID o similar
        return uniqid('report_', true);
    }
}
