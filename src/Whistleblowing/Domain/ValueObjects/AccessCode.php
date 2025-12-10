<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: AccessCode
 * 
 * Representa un código de acceso seguro para recuperar un reporte.
 * Genera un string alfanumérico aleatorio y criptográficamente seguro.
 * Inmutable y con validación estricta.
 */
final class AccessCode
{
    private const LENGTH = 16;
    private const CHARACTERS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private string $value;

    private function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Generar un nuevo AccessCode aleatorio seguro
     */
    public static function generate(): self
    {
        $code = '';
        $maxIndex = strlen(self::CHARACTERS) - 1;

        for ($i = 0; $i < self::LENGTH; $i++) {
            $randomIndex = random_int(0, $maxIndex);
            $code .= self::CHARACTERS[$randomIndex];
        }

        return new self($code);
    }

    /**
     * Crear un AccessCode desde un string existente
     * 
     * @throws InvalidArgumentException Si el código no es válido
     */
    public static function fromString(string $code): self
    {
        return new self($code);
    }

    /**
     * Validar el formato del código
     * 
     * @throws InvalidArgumentException
     */
    private function validate(string $value): void
    {
        if (strlen($value) !== self::LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Access code must be exactly %d characters long', self::LENGTH)
            );
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $value)) {
            throw new InvalidArgumentException(
                'Access code must contain only alphanumeric characters'
            );
        }
    }

    /**
     * Obtener el valor del código
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Convertir a string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Comparar dos AccessCodes por igualdad
     */
    public function equals(AccessCode $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Comparación segura contra timing attacks
     */
    public function equalsSecure(AccessCode $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
