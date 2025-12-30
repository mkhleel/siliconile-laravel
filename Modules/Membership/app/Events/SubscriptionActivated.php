<?php

declare(strict_types=1);

namespace Modules\Membership\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Membership\Models\Subscription;

class SubscriptionActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription
    ) {
    }
}
