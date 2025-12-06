<?php

declare(strict_types=1);

namespace Tests\Unit\Whistleblowing\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Whistleblowing\Domain\ValueObjects\SubmissionId;

/**
 * Unit Test: SubmissionId
 * 
 * Pruebas unitarias para el Value Object SubmissionId
 */
final class SubmissionIdTest extends TestCase
{
    public function test_can_generate_new_submission_id(): void
    {
        $id = SubmissionId::generate();

        $this->assertInstanceOf(SubmissionId::class, $id);
        $this->assertNotEmpty($id->value());
    }

    public function test_can_create_from_valid_uuid_string(): void
    {
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';
        $id = SubmissionId::fromString($uuidString);

        $this->assertInstanceOf(SubmissionId::class, $id);
        $this->assertEquals($uuidString, $id->value());
    }

    public function test_throws_exception_for_invalid_uuid_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        SubmissionId::fromString('invalid-uuid');
    }

    public function test_can_compare_two_submission_ids(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id1 = SubmissionId::fromString($uuid);
        $id2 = SubmissionId::fromString($uuid);
        $id3 = SubmissionId::generate();

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    public function test_can_convert_to_string(): void
    {
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';
        $id = SubmissionId::fromString($uuidString);

        $this->assertEquals($uuidString, (string) $id);
        $this->assertEquals($uuidString, $id->__toString());
    }

    public function test_two_generated_ids_are_different(): void
    {
        $id1 = SubmissionId::generate();
        $id2 = SubmissionId::generate();

        $this->assertFalse($id1->equals($id2));
    }
}
