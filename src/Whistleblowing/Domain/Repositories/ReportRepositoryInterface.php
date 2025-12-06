<?php

namespace Src\Whistleblowing\Domain\Repositories;

use Src\Whistleblowing\Domain\Entities\Report;

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
