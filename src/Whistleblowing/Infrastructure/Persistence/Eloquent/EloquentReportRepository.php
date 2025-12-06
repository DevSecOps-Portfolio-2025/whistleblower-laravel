<?php

namespace Src\Whistleblowing\Infrastructure\Persistence\Eloquent;

use Src\Whistleblowing\Domain\Entities\Report;
use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;
use DateTimeImmutable;

/**
 * Repository Implementation: EloquentReportRepository
 * 
 * Implementación concreta del repositorio usando Eloquent.
 * Traduce entre entidades de dominio y modelos de Eloquent.
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
        $this->model->create([
            'id' => $report->getId(),
            'title' => $report->getTitle(),
            'description' => $report->getDescription(),
            'status' => $report->getStatus(),
            'reporter_id' => $report->getReporterId(),
        ]);
    }

    public function findById(string $id): ?Report
    {
        $model = $this->model->find($id);

        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
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
        $this->model->where('id', $report->getId())->update([
            'title' => $report->getTitle(),
            'description' => $report->getDescription(),
            'status' => $report->getStatus(),
            'reporter_id' => $report->getReporterId(),
        ]);
    }

    public function delete(string $id): void
    {
        $this->model->where('id', $id)->delete();
    }

    /**
     * Convertir un modelo de Eloquent a una entidad de dominio
     */
    private function toDomainEntity(ReportModel $model): Report
    {
        return new Report(
            id: $model->id,
            title: $model->title,
            description: $model->description,
            status: $model->status,
            reporterId: $model->reporter_id,
            createdAt: new DateTimeImmutable($model->created_at->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->updated_at->toDateTimeString())
        );
    }
}
