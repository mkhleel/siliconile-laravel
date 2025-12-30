<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\SpaceBooking\Models\Booking;

/**
 * Event fired when a booking is confirmed.
 */
class BookingConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}
}
