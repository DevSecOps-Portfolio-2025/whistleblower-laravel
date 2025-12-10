<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Value Object: MessageId
 * 
 * Encapsula un identificador único (UUID v4) para un Message.
 * Inmutable y con validación estricta.
 */
final class MessageId
{
    private UuidInterface $value;

    private function __construct(UuidInterface $uuid)
    {
        $this->value = $uuid;
    }

    /**
     * Crear un nuevo MessageId con un UUID v4 generado
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Crear un MessageId desde un string UUID existente
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
     * Convertir a string
     */
    public function __toString(): string
    {
        return $this->value();
    }

    /**
     * Comparar dos MessageIds por igualdad
     */
    public function equals(MessageId $other): bool
    {
        return $this->value->equals($other->value);
    }
}
