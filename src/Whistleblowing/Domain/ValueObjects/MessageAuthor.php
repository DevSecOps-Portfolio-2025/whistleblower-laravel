<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: MessageAuthor
 * 
 * Representa el autor de un mensaje en la comunicación bidireccional.
 * Solo puede ser 'reporter' o 'investigator'.
 */
final class MessageAuthor
{
    private const REPORTER = 'reporter';
    private const INVESTIGATOR = 'investigator';

    private const VALID_AUTHORS = [
        self::REPORTER,
        self::INVESTIGATOR,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Crear un autor tipo 'reporter'
     */
    public static function reporter(): self
    {
        return new self(self::REPORTER);
    }

    /**
     * Crear un autor tipo 'investigator'
     */
    public static function investigator(): self
    {
        return new self(self::INVESTIGATOR);
    }

    /**
     * Crear desde un string
     * 
     * @throws InvalidArgumentException
     */
    public static function fromString(string $author): self
    {
        return new self($author);
    }

    /**
     * Validar el valor del autor
     * 
     * @throws InvalidArgumentException
     */
    private function validate(string $value): void
    {
        if (!in_array($value, self::VALID_AUTHORS, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid message author: %s. Must be one of: %s',
                    $value,
                    implode(', ', self::VALID_AUTHORS)
                )
            );
        }
    }

    /**
     * Obtener el valor del autor
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Verificar si es un reporter
     */
    public function isReporter(): bool
    {
        return $this->value === self::REPORTER;
    }

    /**
     * Verificar si es un investigator
     */
    public function isInvestigator(): bool
    {
        return $this->value === self::INVESTIGATOR;
    }

    /**
     * Convertir a string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Comparar dos MessageAuthors por igualdad
     */
    public function equals(MessageAuthor $other): bool
    {
        return $this->value === $other->value;
    }
}
