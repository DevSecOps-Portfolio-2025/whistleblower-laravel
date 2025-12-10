<?php

namespace Src\Whistleblowing\Infrastructure\Listeners;

use Src\Whistleblowing\Domain\Events\MessageCreated;
use Src\Whistleblowing\Domain\Events\ReportCreated;
use Src\Whistleblowing\Infrastructure\Services\ImmutableAuditService;

class AuditLogListener
{
    public function __construct(
        private readonly ImmutableAuditService $auditService
    ) {}

    /**
     * Handle Report created event.
     */
    public function handleReportCreated(ReportCreated $event): void
    {
        $this->auditService->log(
            action: 'Create',
            entity: $event->report,
            ip: $event->actorIp
        );
    }

    /**
     * Handle Message created event.
     */
    public function handleMessageCreated(MessageCreated $event): void
    {
        $this->auditService->log(
            action: 'Create',
            entity: $event->message,
            ip: $event->actorIp
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            ReportCreated::class => 'handleReportCreated',
            MessageCreated::class => 'handleMessageCreated',
        ];
    }
}
