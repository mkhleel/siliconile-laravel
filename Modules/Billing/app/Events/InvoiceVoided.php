<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Invoice;

/**
 * InvoiceVoided Event - Fired when an invoice is voided/cancelled.
 */
class InvoiceVoided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Invoice $invoice The voided invoice
     * @param string|null $reason The reason for voiding
     */
    public function __construct(
        public readonly Invoice $invoice,
        public readonly ?string $reason = null
    ) {}
}
