<?php

declare(strict_types=1);

namespace Modules\Events\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Events\InvoicePaid;
use Modules\Events\Models\Event;
use Modules\Events\Services\EventBookingService;

/**
 * HandleInvoicePaid Listener
 *
 * Listens for InvoicePaid events from Billing module and
 * confirms event bookings when payment is received.
 */
class HandleInvoicePaid implements ShouldQueue
{
    public function __construct(
        private readonly EventBookingService $bookingService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        // Check if this invoice is for an event booking
        if ($invoice->origin_type !== Event::class) {
            return;
        }

        Log::info('Processing event booking payment', [
            'invoice_id' => $invoice->id,
            'event_id' => $invoice->origin_id,
        ]);

        $this->bookingService->handlePaymentCompleted($invoice);
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(InvoicePaid $event): bool
    {
        return $event->invoice->origin_type === Event::class;
    }
}
