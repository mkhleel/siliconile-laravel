<?php

declare(strict_types=1);

namespace Modules\Events\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Invoice;
use Modules\Events\Models\Attendee;
use Modules\Events\Models\Event;

/**
 * BookingCreated Event
 *
 * Fired when a new event booking is created (tickets reserved, invoice generated).
 */
class BookingCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Event  $event  The event being booked
     * @param  array<Attendee>  $attendees  The attendee records created
     * @param  Invoice|null  $invoice  The invoice (if paid event)
     */
    public function __construct(
        public readonly Event $event,
        public readonly array $attendees,
        public readonly ?Invoice $invoice
    ) {}
}
