<?php

namespace Src\Whistleblowing\Application\UseCases;

use Src\Whistleblowing\Domain\Entities\Report;
use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;

/**
 * Use Case: GetReportByIdUseCase
 * 
 * Caso de uso para obtener un reporte por su ID.
 */
class GetReportByIdUseCase
{
    private ReportRepositoryInterface $reportRepository;

    public function __construct(ReportRepositoryInterface $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    public function execute(string $id): ?Report
    {
        return $this->reportRepository->findById($id);
    }
}
