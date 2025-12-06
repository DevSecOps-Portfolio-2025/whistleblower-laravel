<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\Repositories;

use Src\Whistleblowing\Domain\Entities\Submission;
use Src\Whistleblowing\Domain\ValueObjects\SubmissionId;

/**
 * Repository Interface: SubmissionRepository
 * 
 * Define el contrato para la persistencia de Submissions.
 * La implementación concreta estará en la capa de Infrastructure.
 * 
 * Este es un puerto (Port) en la arquitectura hexagonal.
 */
interface SubmissionRepository
{
    /**
     * Guardar una nueva Submission o actualizar una existente
     * 
     * @param Submission $submission La submission a persistir
     * @return void
     */
    public function save(Submission $submission): void;

    /**
     * Buscar una Submission por su identificador
     * 
     * @param SubmissionId $id El identificador de la submission
     * @return Submission|null La submission encontrada o null si no existe
     */
    public function findById(SubmissionId $id): ?Submission;
}
