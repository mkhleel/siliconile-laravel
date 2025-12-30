<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Models\Payment;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Payment $payment,
        public readonly string $reason = ''
    ) {}
}
