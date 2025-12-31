<?php

declare(strict_types=1);

namespace Modules\Incubation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Incubation\Enums\CohortStatus;
use Modules\Incubation\Models\Cohort;

/**
 * Event fired when a cohort status changes.
 */
class CohortStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Cohort $cohort,
        public CohortStatus $previousStatus,
        public CohortStatus $newStatus
    ) {}
}
