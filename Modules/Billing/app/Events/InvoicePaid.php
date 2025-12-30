<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Invoice;

/**
 * InvoicePaid Event - Fired when an invoice is marked as paid.
 *
 * Other modules (Membership, SpaceBooking) should listen to this event
 * to update their entities when payment is confirmed.
 */
class InvoicePaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Invoice $invoice The paid invoice
     * @param string|null $transactionReference Payment transaction reference
     */
    public function __construct(
        public readonly Invoice $invoice,
        public readonly ?string $transactionReference = null
    ) {}

    /**
     * Get the origin entity (Subscription, Booking, etc.) if available.
     */
    public function getOrigin(): mixed
    {
        return $this->invoice->origin;
    }

    /**
     * Get the billable entity (Member, User, etc.).
     */
    public function getBillable(): mixed
    {
        return $this->invoice->billable;
    }
}
