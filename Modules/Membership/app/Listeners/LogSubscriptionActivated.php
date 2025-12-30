<?php

declare(strict_types=1);

namespace Modules\Membership\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Membership\Events\SubscriptionActivated;
use Modules\Membership\Models\SubscriptionHistory;

class LogSubscriptionActivated
{
    /**
     * Handle the event.
     */
    public function handle(SubscriptionActivated $event): void
    {
        SubscriptionHistory::logStatusChange(
            subscriptionId: $event->subscription->id,
            oldStatus: 'pending',
            newStatus: 'active',
            reason: 'Subscription activated after payment',
            metadata: [
                'plan_id' => $event->subscription->plan_id,
                'member_id' => $event->subscription->member_id,
            ]
        );

        Log::info('Subscription activated', [
            'subscription_id' => $event->subscription->id,
            'member_id' => $event->subscription->member_id,
            'plan_id' => $event->subscription->plan_id,
        ]);
    }
}
