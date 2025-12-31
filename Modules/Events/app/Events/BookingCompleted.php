<?php

declare(strict_types=1);

namespace Modules\Events\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Invoice;
use Modules\Events\Models\Attendee;
use Modules\Events\Models\Event;

/**
 * BookingCompleted Event
 *
 * Fired when a booking is fully completed (payment confirmed, tickets issued).
 */
class BookingCompleted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Event  $event  The event
     * @param  array<Attendee>  $attendees  The confirmed attendees
     * @param  Invoice|null  $invoice  The paid invoice (if applicable)
     */
    public function __construct(
        public readonly Event $event,
        public readonly array $attendees,
        public readonly ?Invoice $invoice
    ) {}
}
