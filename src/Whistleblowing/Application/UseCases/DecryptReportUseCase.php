<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Application\UseCases;

use RuntimeException;
use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;
use Src\Whistleblowing\Infrastructure\Services\RsaKeyService;

/**
 * Use Case: DecryptReportUseCase
 * 
 * Caso de uso para desencriptar el contenido de un reporte.
 * Este caso de uso debe ser usado ÚNICAMENTE por administradores
 * autorizados bajo estrictas políticas de seguridad y auditoría.
 * 
 * ⚠️ ADVERTENCIA DE SEGURIDAD:
 * - Este caso de uso expone información sensible encriptada
 * - Debe implementarse logging de auditoría para cada acceso
 * - Requiere autorización administrativa previa
 * - El uso indebido puede violar políticas de confidencialidad
 */
class DecryptReportUseCase
{
    private ReportRepositoryInterface $reportRepository;
    private RsaKeyService $rsaKeyService;

    public function __construct(
        ReportRepositoryInterface $reportRepository,
        RsaKeyService $rsaKeyService
    ) {
        $this->reportRepository = $reportRepository;
        $this->rsaKeyService = $rsaKeyService;
    }

    /**
     * Ejecuta el desencriptado de un reporte
     * 
     * @param string $id ID del reporte a desencriptar
     * @return string Contenido desencriptado del reporte
     * @throws RuntimeException Si el reporte no existe o no se puede desencriptar
     */
    public function execute(string $id): string
    {
        // Buscar el reporte
        $report = $this->reportRepository->findById($id);
        
        if ($report === null) {
            throw new RuntimeException("Reporte no encontrado con ID: {$id}");
        }

        // Obtener el contenido encriptado
        $encryptedDescription = $report->getDescription();
        
        if (empty($encryptedDescription)) {
            throw new RuntimeException("El reporte no contiene descripción encriptada");
        }

        // Desencriptar usando el servicio RSA
        $decryptedContent = $this->rsaKeyService->decrypt($encryptedDescription);
        
        if ($decryptedContent === null) {
            throw new RuntimeException(
                "Error al desencriptar el reporte. La llave privada podría no coincidir " .
                "o los datos están corruptos."
            );
        }

        // TODO: Implementar logging de auditoría
        // Log::warning('Report decrypted', [
        //     'report_id' => $id,
        //     'admin_user' => auth()->user()->id ?? 'console',
        //     'timestamp' => now(),
        //     'ip_address' => request()->ip() ?? 'N/A'
        // ]);

        return $decryptedContent;
    }
}
