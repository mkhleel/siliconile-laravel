<?php

declare(strict_types=1);

namespace Modules\Membership\Console;

use Illuminate\Console\Command;
use Modules\Membership\Services\SubscriptionService;

class ProcessSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:process-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process subscription lifecycle: expiring, expired, and grace period subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $subscriptionService): int
    {
        $this->info('Processing subscription lifecycle...');

        // Process expiring subscriptions (within 7 days)
        $this->info('Checking for expiring subscriptions...');
        $expiringCount = $subscriptionService->processExpiringSubscriptions(7);
        $this->info("Marked {$expiringCount} subscription(s) as expiring soon.");

        // Process expired subscriptions
        $this->info('Checking for expired subscriptions...');
        $expiredCount = $subscriptionService->processExpiredSubscriptions();
        $this->info("Processed {$expiredCount} expired subscription(s).");

        // Process grace period expiration
        $this->info('Checking for grace period expirations...');
        $gracePeriodCount = $subscriptionService->processGracePeriodExpiration();
        $this->info("Expired {$gracePeriodCount} subscription(s) after grace period.");

        $this->newLine();
        $this->info('✓ Subscription processing complete!');

        return self::SUCCESS;
    }
}
