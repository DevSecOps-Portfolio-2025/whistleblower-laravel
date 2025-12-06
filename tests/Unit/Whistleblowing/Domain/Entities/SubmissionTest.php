<?php

declare(strict_types=1);

namespace Tests\Unit\Whistleblowing\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Whistleblowing\Domain\Entities\Submission;
use Src\Whistleblowing\Domain\ValueObjects\SubmissionId;

/**
 * Unit Test: Submission
 * 
 * Pruebas unitarias para la entidad Submission
 */
final class SubmissionTest extends TestCase
{
    private SubmissionId $id;
    private string $encryptedContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->id = SubmissionId::generate();
        $this->encryptedContent = 'encrypted_content_example_' . bin2hex(random_bytes(16));
    }

    public function test_can_create_new_submission(): void
    {
        $submission = Submission::create($this->id, $this->encryptedContent);

        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertEquals($this->id, $submission->id());
        $this->assertEquals($this->encryptedContent, $submission->encryptedContent());
        $this->assertFalse($submission->isProcessed());
        $this->assertInstanceOf(DateTimeImmutable::class, $submission->createdAt());
    }

    public function test_throws_exception_when_encrypted_content_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Encrypted content cannot be empty');

        Submission::create($this->id, '');
    }

    public function test_throws_exception_when_encrypted_content_is_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Encrypted content cannot be empty');

        Submission::create($this->id, '   ');
    }

    public function test_can_mark_submission_as_processed(): void
    {
        $submission = Submission::create($this->id, $this->encryptedContent);

        $this->assertFalse($submission->isProcessed());

        $submission->markAsProcessed();

        $this->assertTrue($submission->isProcessed());
    }

    public function test_throws_exception_when_marking_already_processed_submission(): void
    {
        $submission = Submission::create($this->id, $this->encryptedContent);
        $submission->markAsProcessed();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is already processed');

        $submission->markAsProcessed();
    }

    public function test_can_reconstitute_submission_from_persistence(): void
    {
        $id = SubmissionId::generate();
        $encryptedContent = 'encrypted_content_from_db';
        $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $isProcessed = true;

        $submission = Submission::reconstitute(
            $id,
            $encryptedContent,
            $createdAt,
            $isProcessed
        );

        $this->assertEquals($id, $submission->id());
        $this->assertEquals($encryptedContent, $submission->encryptedContent());
        $this->assertEquals($createdAt, $submission->createdAt());
        $this->assertTrue($submission->isProcessed());
    }

    public function test_reconstituted_submission_validates_empty_content(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Submission::reconstitute(
            $this->id,
            '',
            new DateTimeImmutable(),
            false
        );
    }

    public function test_created_at_is_immutable(): void
    {
        $submission = Submission::create($this->id, $this->encryptedContent);
        $originalCreatedAt = $submission->createdAt();

        // Intentar modificar la fecha (no debería afectar al objeto interno)
        $modifiedDate = $originalCreatedAt->modify('+1 day');

        $this->assertEquals($originalCreatedAt, $submission->createdAt());
        $this->assertNotEquals($modifiedDate, $submission->createdAt());
    }
}
