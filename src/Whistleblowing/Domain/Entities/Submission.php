<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Whistleblowing\Domain\ValueObjects\SubmissionId;

/**
 * Entity: Submission
 * 
 * Entidad del dominio que representa una denuncia/reporte de whistleblowing.
 * Contiene lógica de negocio y no depende de ningún framework.
 */
final class Submission
{
    private SubmissionId $id;
    private string $encryptedContent;
    private DateTimeImmutable $createdAt;
    private bool $isProcessed;

    /**
     * Constructor privado para forzar el uso de métodos factory
     * 
     * @throws InvalidArgumentException Si el contenido cifrado está vacío
     */
    private function __construct(
        SubmissionId $id,
        string $encryptedContent,
        DateTimeImmutable $createdAt,
        bool $isProcessed
    ) {
        $this->validateEncryptedContent($encryptedContent);

        $this->id = $id;
        $this->encryptedContent = $encryptedContent;
        $this->createdAt = $createdAt;
        $this->isProcessed = $isProcessed;
    }

    /**
     * Crear una nueva Submission
     * 
     * @throws InvalidArgumentException Si el contenido cifrado está vacío
     */
    public static function create(
        SubmissionId $id,
        string $encryptedContent
    ): self {
        return new self(
            $id,
            $encryptedContent,
            new DateTimeImmutable(),
            false
        );
    }

    /**
     * Reconstituir una Submission desde la persistencia
     * 
     * @throws InvalidArgumentException Si el contenido cifrado está vacío
     */
    public static function reconstitute(
        SubmissionId $id,
        string $encryptedContent,
        DateTimeImmutable $createdAt,
        bool $isProcessed
    ): self {
        return new self(
            $id,
            $encryptedContent,
            $createdAt,
            $isProcessed
        );
    }

    /**
     * Validar que el contenido cifrado no esté vacío
     * 
     * @throws InvalidArgumentException
     */
    private function validateEncryptedContent(string $encryptedContent): void
    {
        if (trim($encryptedContent) === '') {
            throw new InvalidArgumentException(
                'Encrypted content cannot be empty'
            );
        }
    }

    /**
     * Marcar la submission como procesada
     * Método de negocio que refleja una regla del dominio
     */
    public function markAsProcessed(): void
    {
        if ($this->isProcessed) {
            throw new InvalidArgumentException(
                sprintf('Submission %s is already processed', $this->id->value())
            );
        }

        $this->isProcessed = true;
    }

    /**
     * Obtener el identificador de la submission
     */
    public function id(): SubmissionId
    {
        return $this->id;
    }

    /**
     * Obtener el contenido cifrado
     */
    public function encryptedContent(): string
    {
        return $this->encryptedContent;
    }

    /**
     * Obtener la fecha de creación
     */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Verificar si la submission ha sido procesada
     */
    public function isProcessed(): bool
    {
        return $this->isProcessed;
    }
}
