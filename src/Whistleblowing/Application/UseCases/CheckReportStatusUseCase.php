<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Application\UseCases;

use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;
use Src\Whistleblowing\Domain\ValueObjects\AccessCode;
use Src\Whistleblowing\Application\DTOs\ReportStatusDTO;
use Src\Whistleblowing\Application\DTOs\MessageDTO;

/**
 * Use Case: CheckReportStatusUseCase
 * 
 * Permite consultar el estado de un reporte usando el AccessCode.
 * Retorna el reporte completo con todos los mensajes de la conversación.
 * 
 * Este es el caso de uso principal para la comunicación bidireccional anónima.
 */
class CheckReportStatusUseCase
{
    private ReportRepositoryInterface $reportRepository;

    public function __construct(ReportRepositoryInterface $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * Ejecutar el caso de uso
     * 
     * @param string $accessCodeString Código de acceso en texto plano
     * @return ReportStatusDTO
     * @throws \InvalidArgumentException Si el AccessCode es inválido
     * @throws \RuntimeException Si el reporte no se encuentra
     */
    public function execute(string $accessCodeString): ReportStatusDTO
    {
        // Validar y reconstituir el AccessCode
        try {
            $accessCode = AccessCode::fromString($accessCodeString);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                'Código de acceso inválido. Debe tener 16 caracteres alfanuméricos.'
            );
        }

        // Buscar el reporte por AccessCode
        $report = $this->reportRepository->findByAccessCode($accessCode);

        if (!$report) {
            throw new \RuntimeException(
                'No se encontró ningún reporte con este código de acceso.'
            );
        }

        // Verificar que el AccessCode coincida (seguridad adicional)
        if (!$report->hasAccessCode($accessCode)) {
            throw new \RuntimeException(
                'El código de acceso no coincide con el reporte.'
            );
        }

        // Convertir mensajes a DTOs
        $messageDTOs = array_map(
            fn($message) => new MessageDTO(
                id: $message->getId()->value(),
                content: $message->getContent(),
                author: $message->getAuthor()->value(),
                createdAt: $message->getCreatedAt()->format('Y-m-d H:i:s')
            ),
            $report->getMessages()
        );

        // Retornar DTO con toda la información
        return new ReportStatusDTO(
            reportId: $report->getId(),
            title: $report->getTitle(),
            description: $report->getDescription(),
            status: $report->getStatus(),
            isAnonymous: $report->isAnonymous(),
            messages: $messageDTOs,
            messageCount: $report->getMessageCount(),
            createdAt: $report->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $report->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }
}
