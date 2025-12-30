<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Invoice;

/**
 * InvoiceFinalized Event - Fired when an invoice is finalized (number assigned).
 */
class InvoiceFinalized
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Invoice $invoice
    ) {}
}
