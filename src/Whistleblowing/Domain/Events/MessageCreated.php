<?php

namespace Src\Whistleblowing\Domain\Events;

use Src\Whistleblowing\Domain\Entities\Message;

class MessageCreated
{
    public function __construct(
        public readonly Message $message,
        public readonly ?string $actorIp = null
    ) {}
}
