<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\Repositories;

use Src\Whistleblowing\Domain\Entities\Report;
use Src\Whistleblowing\Domain\ValueObjects\AccessCode;

/**
 * Repository Interface: ReportRepositoryInterface
 * 
 * Define el contrato para la persistencia de reportes.
 * La implementación concreta estará en la capa de Infrastructure.
 */
interface ReportRepositoryInterface
{
    /**
     * Guardar un nuevo reporte
     */
    public function save(Report $report): void;

    /**
     * Encontrar un reporte por ID
     */
    public function findById(string $id): ?Report;

    /**
     * Encontrar un reporte por su AccessCode
     */
    public function findByAccessCode(AccessCode $code): ?Report;

    /**
     * Obtener todos los reportes
     * 
     * @return Report[]
     */
    public function findAll(): array;

    /**
     * Encontrar reportes por estado
     * 
     * @return Report[]
     */
    public function findByStatus(string $status): array;

    /**
     * Actualizar un reporte existente
     */
    public function update(Report $report): void;

    /**
     * Eliminar un reporte
     */
    public function delete(string $id): void;
}
