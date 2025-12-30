<?php

declare(strict_types=1);

namespace Modules\Membership\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Membership\Events\SubscriptionExpired;
use Modules\Membership\Models\SubscriptionHistory;

class LogSubscriptionExpired
{
    /**
     * Handle the event.
     */
    public function handle(SubscriptionExpired $event): void
    {
        SubscriptionHistory::logStatusChange(
            subscriptionId: $event->subscription->id,
            oldStatus: $event->subscription->status->value,
            newStatus: 'expired',
            reason: 'Subscription expired naturally',
            metadata: [
                'end_date' => $event->subscription->end_date->toDateString(),
                'plan_id' => $event->subscription->plan_id,
            ]
        );

        Log::warning('Subscription expired', [
            'subscription_id' => $event->subscription->id,
            'member_id' => $event->subscription->member_id,
            'end_date' => $event->subscription->end_date,
        ]);
    }
}
