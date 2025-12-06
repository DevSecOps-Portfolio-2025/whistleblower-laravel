<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Value Object: SubmissionId
 * 
 * Encapsula un identificador único (UUID v4) para una Submission.
 * Inmutable y con validación estricta.
 */
final class SubmissionId
{
    private UuidInterface $value;

    private function __construct(UuidInterface $uuid)
    {
        $this->value = $uuid;
    }

    /**
     * Crear un nuevo SubmissionId con un UUID v4 generado
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Crear un SubmissionId desde un string UUID existente
     * 
     * @throws InvalidArgumentException Si el string no es un UUID válido
     */
    public static function fromString(string $uuid): self
    {
        if (!Uuid::isValid($uuid)) {
            throw new InvalidArgumentException(
                sprintf('Invalid UUID format: %s', $uuid)
            );
        }

        return new self(Uuid::fromString($uuid));
    }

    /**
     * Obtener el valor del UUID como string
     */
    public function value(): string
    {
        return $this->value->toString();
    }

    /**
     * Comparar si dos SubmissionIds son iguales
     */
    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    /**
     * Representación en string del SubmissionId
     */
    public function __toString(): string
    {
        return $this->value();
    }
}
