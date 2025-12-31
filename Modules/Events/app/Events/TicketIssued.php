<?php

declare(strict_types=1);

namespace Modules\Events\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Events\Models\Attendee;

/**
 * TicketIssued Event
 *
 * Fired when a ticket is issued to an attendee.
 */
class TicketIssued
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Attendee $attendee
    ) {}
}
