<?php

declare(strict_types=1);

namespace Modules\Membership\Services;

use Modules\Membership\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Enums\SubscriptionStatus;
use Modules\Membership\Events\SubscriptionActivated;
use Modules\Membership\Events\SubscriptionCancelled;
use Modules\Membership\Events\SubscriptionCreated;
use Modules\Membership\Events\SubscriptionExpired;
use Modules\Membership\Events\SubscriptionExpiring;
use Modules\Membership\Events\SubscriptionRenewed;
use Modules\Membership\Events\SubscriptionSuspended;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Subscription;
use Modules\Membership\Models\SubscriptionHistory;

class SubscriptionService
{
    /**
     * Create a new subscription.
     */
    public function createSubscription(
        Member $member,
        Plan $plan,
        bool $autoRenew = false,
        int $gracePeriodDays = 0
    ): Subscription {
        return DB::transaction(function () use ($member, $plan, $autoRenew, $gracePeriodDays) {
            $startDate = now();
            $endDate = $startDate->copy()->addDays($plan->duration_days);

            $subscription = Subscription::create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'next_billing_date' => $autoRenew ? $endDate : null,
                'status' => SubscriptionStatus::PENDING,
                'auto_renew' => $autoRenew,
                'grace_period_days' => $gracePeriodDays,
                'price_at_subscription' => $plan->price,
                'currency' => $plan->currency,
            ]);

            event(new SubscriptionCreated($subscription));

            return $subscription;
        });
    }

    /**
     * Activate a subscription (typically after payment).
     */
    public function activateSubscription(Subscription $subscription, ?int $paymentId = null): Subscription
    {
        return DB::transaction(function () use ($subscription, $paymentId) {
            $oldStatus = $subscription->status;

            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE,
                'activated_at' => now(),
                'last_payment_id' => $paymentId,
                'last_payment_at' => $paymentId ? now() : null,
            ]);

            SubscriptionHistory::logStatusChange(
                subscriptionId: $subscription->id,
                oldStatus: $oldStatus->value,
                newStatus: SubscriptionStatus::ACTIVE->value,
                reason: 'Subscription activated after payment',
                metadata: ['payment_id' => $paymentId]
            );

            event(new SubscriptionActivated($subscription));

            return $subscription->fresh();
        });
    }

    /**
     * Renew an existing subscription.
     */
    public function renewSubscription(
        Subscription $subscription,
        ?int $paymentId = null
    ): Subscription {
        return DB::transaction(function () use ($subscription, $paymentId) {
            $plan = $subscription->plan;
            $newEndDate = $subscription->end_date->copy()->addDays($plan->duration_days);

            $subscription->update([
                'end_date' => $newEndDate,
                'next_billing_date' => $subscription->auto_renew ? $newEndDate : null,
                'status' => SubscriptionStatus::ACTIVE,
                'last_payment_id' => $paymentId,
                'last_payment_at' => $paymentId ? now() : null,
            ]);

            SubscriptionHistory::logStatusChange(
                subscriptionId: $subscription->id,
                oldStatus: 'expiring',
                newStatus: SubscriptionStatus::ACTIVE->value,
                reason: 'Subscription renewed',
                metadata: [
                    'new_end_date' => $newEndDate->toDateString(),
                    'payment_id' => $paymentId,
                ]
            );

            event(new SubscriptionRenewed($subscription));

            return $subscription->fresh();
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(
        Subscription $subscription,
        string $reason,
        ?int $cancelledBy = null
    ): Subscription {
        return DB::transaction(function () use ($subscription, $reason, $cancelledBy) {
            $oldStatus = $subscription->status;

            $subscription->update([
                'status' => SubscriptionStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy,
                'auto_renew' => false,
            ]);

            SubscriptionHistory::logStatusChange(
                subscriptionId: $subscription->id,
                oldStatus: $oldStatus->value,
                newStatus: SubscriptionStatus::CANCELLED->value,
                reason: $reason,
                changedBy: $cancelledBy
            );

            event(new SubscriptionCancelled($subscription, $reason));

            return $subscription->fresh();
        });
    }

    /**
     * Suspend a subscription (admin action).
     */
    public function suspendSubscription(
        Subscription $subscription,
        string $reason,
        int $suspendedBy
    ): Subscription {
        return DB::transaction(function () use ($subscription, $reason, $suspendedBy) {
            $oldStatus = $subscription->status;

            $subscription->update([
                'status' => SubscriptionStatus::SUSPENDED,
                'auto_renew' => false,
            ]);

            SubscriptionHistory::logStatusChange(
                subscriptionId: $subscription->id,
                oldStatus: $oldStatus->value,
                newStatus: SubscriptionStatus::SUSPENDED->value,
                reason: $reason,
                changedBy: $suspendedBy
            );

            event(new SubscriptionSuspended($subscription, $reason));

            return $subscription->fresh();
        });
    }

    /**
     * Mark subscription as expiring soon.
     */
    public function markAsExpiring(Subscription $subscription): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::ACTIVE) {
            return $subscription;
        }

        $daysRemaining = $subscription->daysRemaining();

        $subscription->update([
            'status' => SubscriptionStatus::EXPIRING,
        ]);

        SubscriptionHistory::logStatusChange(
            subscriptionId: $subscription->id,
            oldStatus: SubscriptionStatus::ACTIVE->value,
            newStatus: SubscriptionStatus::EXPIRING->value,
            reason: "Subscription expiring in {$daysRemaining} days"
        );

        event(new SubscriptionExpiring($subscription, $daysRemaining));

        return $subscription->fresh();
    }

    /**
     * Mark subscription as expired.
     */
    public function markAsExpired(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $oldStatus = $subscription->status;

            // Check if grace period should be applied
            if ($subscription->grace_period_days > 0) {
                $subscription->update([
                    'status' => SubscriptionStatus::GRACE_PERIOD,
                ]);

                SubscriptionHistory::logStatusChange(
                    subscriptionId: $subscription->id,
                    oldStatus: $oldStatus->value,
                    newStatus: SubscriptionStatus::GRACE_PERIOD->value,
                    reason: "Subscription entered grace period ({$subscription->grace_period_days} days)"
                );
            } else {
                $subscription->update([
                    'status' => SubscriptionStatus::EXPIRED,
                ]);

                SubscriptionHistory::logStatusChange(
                    subscriptionId: $subscription->id,
                    oldStatus: $oldStatus->value,
                    newStatus: SubscriptionStatus::EXPIRED->value,
                    reason: 'Subscription expired'
                );

                event(new SubscriptionExpired($subscription));
            }

            return $subscription->fresh();
        });
    }

    /**
     * Process subscriptions that are expiring within X days.
     */
    public function processExpiringSubscriptions(int $days = 7): int
    {
        $subscriptions = Subscription::expiringWithinDays($days)->get();
        $count = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->markAsExpiring($subscription);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to process expiring subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Process expired subscriptions.
     */
    public function processExpiredSubscriptions(): int
    {
        $subscriptions = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::EXPIRING])
            ->where('end_date', '<', now())
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->markAsExpired($subscription);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to process expired subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Process grace period expiration.
     */
    public function processGracePeriodExpiration(): int
    {
        $subscriptions = Subscription::gracePeriod()->get();
        $count = 0;

        foreach ($subscriptions as $subscription) {
            $gracePeriodEnd = $subscription->end_date->copy()->addDays($subscription->grace_period_days);

            if ($gracePeriodEnd->isPast()) {
                try {
                    $subscription->update([
                        'status' => SubscriptionStatus::EXPIRED,
                    ]);

                    SubscriptionHistory::logStatusChange(
                        subscriptionId: $subscription->id,
                        oldStatus: SubscriptionStatus::GRACE_PERIOD->value,
                        newStatus: SubscriptionStatus::EXPIRED->value,
                        reason: 'Grace period ended'
                    );

                    event(new SubscriptionExpired($subscription));
                    $count++;
                } catch (\Exception $e) {
                    Log::error('Failed to process grace period expiration', [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $count;
    }

    /**
     * Get subscription summary for a member.
     */
    public function getSubscriptionSummary(Member $member): array
    {
        $activeSubscription = $member->activeSubscription();

        return [
            'has_active_subscription' => $activeSubscription !== null,
            'current_subscription' => $activeSubscription,
            'days_remaining' => $activeSubscription?->daysRemaining(),
            'is_expiring_soon' => $activeSubscription?->isExpiringSoon(),
            'is_in_grace_period' => $activeSubscription?->isInGracePeriod(),
            'grace_period_days_remaining' => $activeSubscription?->gracePeriodDaysRemaining(),
            'total_subscriptions' => $member->subscriptions()->count(),
            'auto_renew_enabled' => $activeSubscription?->auto_renew ?? false,
        ];
    }
}
