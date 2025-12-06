<?php

namespace Src\Whistleblowing\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: ReportStatus
 * 
 * Objeto de valor que encapsula el estado de un reporte.
 * Inmutable y con validación de negocio.
 */
final class ReportStatus
{
    public const PENDING = 'pending';
    public const UNDER_REVIEW = 'under_review';
    public const REVIEWED = 'reviewed';
    public const RESOLVED = 'resolved';
    public const REJECTED = 'rejected';

    private const VALID_STATUSES = [
        self::PENDING,
        self::UNDER_REVIEW,
        self::REVIEWED,
        self::RESOLVED,
        self::REJECTED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                "Invalid status: {$value}. Valid statuses are: " . implode(', ', self::VALID_STATUSES)
            );
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function underReview(): self
    {
        return new self(self::UNDER_REVIEW);
    }

    public static function reviewed(): self
    {
        return new self(self::REVIEWED);
    }

    public static function resolved(): self
    {
        return new self(self::RESOLVED);
    }

    public static function rejected(): self
    {
        return new self(self::REJECTED);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
