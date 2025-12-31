<?php

declare(strict_types=1);

namespace Modules\Events\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Events\Models\Attendee;

/**
 * AttendeeCheckedIn Event
 *
 * Fired when an attendee is checked in to an event.
 */
class AttendeeCheckedIn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Attendee $attendee,
        public readonly string $method = 'manual'
    ) {}
}
