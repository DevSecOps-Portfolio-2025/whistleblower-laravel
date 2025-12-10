<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Infrastructure\Persistence\Eloquent;

use Src\Whistleblowing\Domain\Entities\Report;
use Src\Whistleblowing\Domain\Entities\Message;
use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;
use Src\Whistleblowing\Domain\ValueObjects\AccessCode;
use Src\Whistleblowing\Domain\ValueObjects\MessageId;
use Src\Whistleblowing\Domain\ValueObjects\MessageAuthor;
use DateTimeImmutable;

/**
 * Repository Implementation: EloquentReportRepository
 * 
 * Implementación concreta del repositorio usando Eloquent.
 * Traduce entre entidades de dominio y modelos de Eloquent.
 * 
 * SEGURIDAD: El AccessCode se hashea con SHA-256 antes de almacenarse.
 */
class EloquentReportRepository implements ReportRepositoryInterface
{
    private ReportModel $model;

    public function __construct(ReportModel $model)
    {
        $this->model = $model;
    }

    public function save(Report $report): void
    {
        // Crear el reporte
        $reportModel = $this->model->create([
            'id' => $report->getId(),
            'title' => $report->getTitle(),
            'description' => $report->getDescription(),
            'status' => $report->getStatus(),
            'reporter_id' => $report->getReporterId(),
            'access_code_hash' => $this->hashAccessCode($report->getAccessCode()),
        ]);

        // Guardar mensajes si existen
        $this->saveMessages($reportModel, $report->getMessages());
    }

    public function findById(string $id): ?Report
    {
        $model = $this->model->with('messages')->find($id);

        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByAccessCode(AccessCode $code): ?Report
    {
        $hash = $this->hashAccessCode($code);
        
        $model = $this->model->with('messages')
                             ->where('access_code_hash', $hash)
                             ->first();

        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model, $code);
    }

    public function findAll(): array
    {
        $models = $this->model->all();

        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByStatus(string $status): array
    {
        $models = $this->model->where('status', $status)->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function update(Report $report): void
    {
        $reportModel = $this->model->find($report->getId());
        
        if (!$reportModel) {
            throw new \RuntimeException("Report not found: {$report->getId()}");
        }

        // Actualizar el reporte
        $reportModel->update([
            'title' => $report->getTitle(),
            'description' => $report->getDescription(),
            'status' => $report->getStatus(),
            'reporter_id' => $report->getReporterId(),
            'access_code_hash' => $this->hashAccessCode($report->getAccessCode()),
        ]);

        // Sincronizar mensajes: eliminar viejos y crear nuevos
        $reportModel->messages()->delete();
        $this->saveMessages($reportModel, $report->getMessages());
    }

    public function delete(string $id): void
    {
        $this->model->where('id', $id)->delete();
    }

    /**
     * Convertir un modelo de Eloquent a una entidad de dominio
     */
    private function toDomainEntity(ReportModel $model, ?AccessCode $accessCode = null): Report
    {
        // Si no se proporciona el AccessCode, crear uno dummy (no se podrá usar)
        // En findById no tenemos el código original, solo el hash
        $code = $accessCode ?? AccessCode::fromString('0000000000000000');

        // Convertir mensajes
        $messages = $model->messages->map(function ($messageModel) {
            return new Message(
                id: MessageId::fromString($messageModel->id),
                content: $messageModel->content,
                author: MessageAuthor::fromString($messageModel->author),
                createdAt: new DateTimeImmutable($messageModel->created_at->toDateTimeString())
            );
        })->toArray();

        return new Report(
            id: $model->id,
            title: $model->title,
            description: $model->description,
            accessCode: $code,
            status: $model->status,
            reporterId: $model->reporter_id,
            messages: $messages,
            createdAt: new DateTimeImmutable($model->created_at->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->updated_at->toDateTimeString())
        );
    }

    /**
     * Hashear el AccessCode para almacenamiento seguro
     */
    private function hashAccessCode(AccessCode $code): string
    {
        return hash('sha256', $code->value());
    }

    /**
     * Guardar mensajes asociados al reporte
     * 
     * @param Message[] $messages
     */
    private function saveMessages(ReportModel $reportModel, array $messages): void
    {
        foreach ($messages as $message) {
            MessageModel::create([
                'id' => $message->getId()->value(),
                'report_id' => $reportModel->id,
                'content' => $message->getContent(),
                'author' => $message->getAuthor()->value(),
                'created_at' => $message->getCreatedAt(),
            ]);
        }
    }
}
