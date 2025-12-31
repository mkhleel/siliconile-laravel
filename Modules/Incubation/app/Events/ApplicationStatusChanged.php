<?php

declare(strict_types=1);

namespace Modules\Incubation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Models\Application;

/**
 * Event fired when an application status changes.
 */
class ApplicationStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Application $application,
        public ApplicationStatus $previousStatus,
        public ApplicationStatus $newStatus
    ) {}
}
