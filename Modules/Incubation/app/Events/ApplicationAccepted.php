<?php

declare(strict_types=1);

namespace Modules\Incubation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Incubation\Models\Application;

/**
 * Event fired when an application is accepted.
 *
 * This event is listened to by the Membership module to create
 * the startup's member record and assign an incubation plan.
 */
class ApplicationAccepted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Application $application
    ) {}
}
