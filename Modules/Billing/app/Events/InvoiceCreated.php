<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Invoice;

/**
 * InvoiceCreated Event - Fired when a new invoice is created.
 */
class InvoiceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Invoice $invoice
    ) {}
}
