<?php

namespace Src\Whistleblowing\Domain\Events;

use Src\Whistleblowing\Domain\Entities\Report;

class ReportCreated
{
    public function __construct(
        public readonly Report $report,
        public readonly ?string $actorIp = null
    ) {}
}
