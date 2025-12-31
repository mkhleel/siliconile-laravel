<?php

declare(strict_types=1);

namespace Modules\Incubation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Incubation\Models\MentorshipSession;

/**
 * Event fired when a mentorship session is booked.
 */
class MentorshipSessionBooked
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public MentorshipSession $session
    ) {}
}
