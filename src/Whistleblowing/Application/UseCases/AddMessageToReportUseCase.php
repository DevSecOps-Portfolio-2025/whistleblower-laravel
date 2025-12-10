<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Application\UseCases;

use Illuminate\Support\Facades\Event;
use Src\Whistleblowing\Domain\Entities\Message;
use Src\Whistleblowing\Domain\Events\MessageCreated;
use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;
use Src\Whistleblowing\Domain\ValueObjects\AccessCode;
use Src\Whistleblowing\Domain\ValueObjects\MessageAuthor;

/**
 * Use Case: AddMessageToReportUseCase
 * 
 * Permite agregar un mensaje a un reporte existente.
 * El autor puede ser 'reporter' o 'investigator'.
 * 
 * Este caso de uso implementa la comunicación bidireccional.
 */
class AddMessageToReportUseCase
{
    private ReportRepositoryInterface $reportRepository;

    public function __construct(ReportRepositoryInterface $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * Ejecutar el caso de uso
     * 
     * @param string $accessCodeString Código de acceso
     * @param string $content Contenido del mensaje
     * @param string $author 'reporter' o 'investigator'
     * @param string|null $actorIp IP del actor (opcional)
     * @return array Información del mensaje creado
     * @throws \InvalidArgumentException Si los datos son inválidos
     * @throws \RuntimeException Si el reporte no se encuentra
     */
    public function execute(string $accessCodeString, string $content, string $author, ?string $actorIp = null): array
    {
        // Validaciones
        $this->validateMessageContent($content);

        // Reconstituir el AccessCode
        try {
            $accessCode = AccessCode::fromString($accessCodeString);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Código de acceso inválido.');
        }

        // Buscar el reporte
        $report = $this->reportRepository->findByAccessCode($accessCode);

        if (!$report) {
            throw new \RuntimeException('Reporte no encontrado.');
        }

        // Verificar AccessCode
        if (!$report->hasAccessCode($accessCode)) {
            throw new \RuntimeException('Código de acceso inválido.');
        }

        // Crear el mensaje
        try {
            $messageAuthor = MessageAuthor::fromString($author);
            $message = Message::create($content, $messageAuthor);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                "Autor inválido. Debe ser 'reporter' o 'investigator'."
            );
        }

        // Agregar mensaje al reporte
        $report->addMessage($message);

        // Persistir cambios
        $this->reportRepository->update($report);

        // Disparar evento de dominio para auditoría
        Event::dispatch(new MessageCreated($message, $actorIp));

        // Retornar información del mensaje creado
        return [
            'message_id' => $message->getId()->value(),
            'content' => $message->getContent(),
            'author' => $message->getAuthor()->value(),
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'report_id' => $report->getId(),
            'message_count' => $report->getMessageCount(),
        ];
    }

    private function validateMessageContent(string $content): void
    {
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('El contenido del mensaje no puede estar vacío.');
        }

        if (strlen($content) < 3) {
            throw new \InvalidArgumentException('El mensaje debe tener al menos 3 caracteres.');
        }

        if (strlen($content) > 5000) {
            throw new \InvalidArgumentException('El mensaje no puede exceder 5000 caracteres.');
        }
    }
}
