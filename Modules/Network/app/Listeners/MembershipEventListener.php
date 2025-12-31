<?php

declare(strict_types=1);

namespace Modules\Network\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Events\SubscriptionActivated;
use Modules\Membership\Events\SubscriptionCancelled;
use Modules\Membership\Events\SubscriptionCreated;
use Modules\Membership\Events\SubscriptionExpired;
use Modules\Membership\Events\SubscriptionRenewed;
use Modules\Membership\Events\SubscriptionSuspended;
use Modules\Network\Jobs\KickMikrotikUserJob;
use Modules\Network\Jobs\SyncMikrotikUserJob;
use Modules\Network\Settings\RouterSettings;

/**
 * Listener for Membership module events.
 *
 * Handles network access management based on subscription status changes.
 */
class MembershipEventListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected RouterSettings $settings
    ) {}

    /**
     * Handle subscription created events.
     *
     * When a new subscription is created, sync the member to Mikrotik.
     */
    public function handleSubscriptionCreated(SubscriptionCreated $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $member = $event->subscription->member;

        Log::info('MembershipEventListener: Processing SubscriptionCreated', [
            'subscription_id' => $event->subscription->id,
            'member_id' => $member->id,
        ]);

        SyncMikrotikUserJob::dispatch($member);
    }

    /**
     * Handle subscription activated events.
     *
     * When a subscription is activated, ensure the member has access.
     */
    public function handleSubscriptionActivated(SubscriptionActivated $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $member = $event->subscription->member;

        Log::info('MembershipEventListener: Processing SubscriptionActivated', [
            'subscription_id' => $event->subscription->id,
            'member_id' => $member->id,
        ]);

        SyncMikrotikUserJob::dispatch($member);
    }

    /**
     * Handle subscription renewed events.
     *
     * When subscription is renewed, ensure access is enabled.
     */
    public function handleSubscriptionRenewed(SubscriptionRenewed $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $member = $event->subscription->member;

        Log::info('MembershipEventListener: Processing SubscriptionRenewed', [
            'subscription_id' => $event->subscription->id,
            'member_id' => $member->id,
        ]);

        // Re-sync to ensure user is enabled
        SyncMikrotikUserJob::dispatch($member);
    }

    /**
     * Handle subscription expired events.
     *
     * When subscription expires, disable access and kick active sessions.
     */
    public function handleSubscriptionExpired(SubscriptionExpired $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $member = $event->subscription->member;

        Log::info('MembershipEventListener: Processing SubscriptionExpired', [
            'subscription_id' => $event->subscription->id,
            'member_id' => $member->id,
        ]);

        // Disable and kick the user
        KickMikrotikUserJob::dispatch($member, disableUser: true);
    }

    /**
     * Handle subscription cancelled events.
     *
     * When subscription is cancelled, disable access.
     */
    public function handleSubscriptionCancelled(SubscriptionCancelled $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $member = $event->subscription->member;

        Log::info('MembershipEventListener: Processing SubscriptionCancelled', [
            'subscription_id' => $event->subscription->id,
            'member_id' => $member->id,
        ]);

        // Disable and kick the user
        KickMikrotikUserJob::dispatch($member, disableUser: true);
    }

    /**
     * Handle subscription suspended events.
     *
     * When subscription is suspended (e.g., payment failed), disable access.
     */
    public function handleSubscriptionSuspended(SubscriptionSuspended $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $member = $event->subscription->member;

        Log::info('MembershipEventListener: Processing SubscriptionSuspended', [
            'subscription_id' => $event->subscription->id,
            'member_id' => $member->id,
        ]);

        // Disable and kick the user
        KickMikrotikUserJob::dispatch($member, disableUser: true);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe($events): array
    {
        return [
            SubscriptionCreated::class => 'handleSubscriptionCreated',
            SubscriptionActivated::class => 'handleSubscriptionActivated',
            SubscriptionRenewed::class => 'handleSubscriptionRenewed',
            SubscriptionExpired::class => 'handleSubscriptionExpired',
            SubscriptionCancelled::class => 'handleSubscriptionCancelled',
            SubscriptionSuspended::class => 'handleSubscriptionSuspended',
        ];
    }

    /**
     * Check if the Network module is enabled.
     */
    protected function isEnabled(): bool
    {
        return $this->settings->enabled && $this->settings->isConfigured();
    }
}
