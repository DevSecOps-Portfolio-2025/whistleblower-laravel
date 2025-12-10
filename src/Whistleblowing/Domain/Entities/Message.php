<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Domain\Entities;

use DateTimeImmutable;
use Src\Whistleblowing\Domain\ValueObjects\MessageId;
use Src\Whistleblowing\Domain\ValueObjects\MessageAuthor;

/**
 * Entity: Message
 * 
 * Representa un mensaje individual en la comunicación bidireccional
 * entre el denunciante y el investigador.
 * 
 * El contenido del mensaje se almacena en texto plano por simplicidad en el MVP.
 * Para producción, considerar encriptación simétrica del contenido.
 */
class Message
{
    private MessageId $id;
    private string $content;
    private MessageAuthor $author;
    private DateTimeImmutable $createdAt;

    public function __construct(
        MessageId $id,
        string $content,
        MessageAuthor $author,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->content = $content;
        $this->author = $author;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    /**
     * Factory method para crear un nuevo mensaje
     */
    public static function create(
        string $content,
        MessageAuthor $author
    ): self {
        return new self(
            MessageId::generate(),
            $content,
            $author
        );
    }

    /**
     * Factory method para crear mensaje de un reporter
     */
    public static function fromReporter(string $content): self
    {
        return self::create($content, MessageAuthor::reporter());
    }

    /**
     * Factory method para crear mensaje de un investigator
     */
    public static function fromInvestigator(string $content): self
    {
        return self::create($content, MessageAuthor::investigator());
    }

    // Getters
    public function getId(): MessageId
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getAuthor(): MessageAuthor
    {
        return $this->author;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Métodos de negocio
    public function isFromReporter(): bool
    {
        return $this->author->isReporter();
    }

    public function isFromInvestigator(): bool
    {
        return $this->author->isInvestigator();
    }

    /**
     * Obtener el contenido truncado para preview
     */
    public function getContentPreview(int $length = 100): string
    {
        if (mb_strlen($this->content) <= $length) {
            return $this->content;
        }

        return mb_substr($this->content, 0, $length) . '...';
    }
}
