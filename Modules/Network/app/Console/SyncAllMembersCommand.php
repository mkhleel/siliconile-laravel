<?php

declare(strict_types=1);

namespace Modules\Network\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Enums\SubscriptionStatus;
use Modules\Membership\Models\Member;
use Modules\Network\Jobs\KickMikrotikUserJob;
use Modules\Network\Jobs\SyncMikrotikUserJob;
use Modules\Network\Services\MikrotikService;

/**
 * Command to sync all members to Mikrotik router.
 *
 * Useful for initial setup, router reboot recovery, or periodic reconciliation.
 */
class SyncAllMembersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'network:sync-all
        {--force : Force sync even if Network module is disabled}
        {--dry-run : Show what would be synced without making changes}
        {--sync-queue : Use queue instead of sync processing}';

    /**
     * The console command description.
     */
    protected $description = 'Sync all members to Mikrotik router based on their subscription status';

    /**
     * Execute the console command.
     */
    public function handle(MikrotikService $mikrotikService): int
    {
        $this->info('🔄 Starting Mikrotik sync for all members...');

        // Check if module is available
        if (! $mikrotikService->isAvailable() && ! $this->option('force')) {
            $this->error('❌ Network module is not enabled or configured.');
            $this->line('   Use --force to run anyway, or configure the module first.');

            return self::FAILURE;
        }

        // Test connection first
        $this->info('📡 Testing connection to router...');
        $testResult = $mikrotikService->testConnection();

        if (! $testResult['success']) {
            $this->error("❌ Cannot connect to router: {$testResult['message']}");

            return self::FAILURE;
        }

        $this->info("✅ Connected to: {$testResult['identity']}");
        $this->newLine();

        // Get all active members with subscriptions
        $activeMembers = Member::query()
            ->with(['user', 'subscriptions' => fn ($q) => $q->active()])
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', SubscriptionStatus::ACTIVE);
            })
            ->get();

        // Get members with expired subscriptions (to disable)
        $expiredMembers = Member::query()
            ->with(['user', 'subscriptions' => fn ($q) => $q->expired()])
            ->whereHas('subscriptions', function ($query) {
                $query->whereIn('status', [
                    SubscriptionStatus::EXPIRED,
                    SubscriptionStatus::CANCELLED,
                    SubscriptionStatus::SUSPENDED,
                ]);
            })
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', SubscriptionStatus::ACTIVE);
            })
            ->get();

        $this->info("📊 Found {$activeMembers->count()} active members to enable");
        $this->info("📊 Found {$expiredMembers->count()} expired members to disable");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();

            $this->displayMembersTable('Active Members (will be enabled)', $activeMembers);
            $this->displayMembersTable('Expired Members (will be disabled)', $expiredMembers);

            return self::SUCCESS;
        }

        // Process active members
        $this->info('🟢 Processing active members...');
        $bar = $this->output->createProgressBar($activeMembers->count());
        $bar->start();

        $enabledCount = 0;
        $failedCount = 0;

        foreach ($activeMembers as $member) {
            try {
                if ($this->option('sync-queue')) {
                    SyncMikrotikUserJob::dispatch($member);
                } else {
                    $mikrotikService->syncUser($member);
                }
                $enabledCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('network:sync-all - Failed to sync member', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Process expired members
        $this->info('🔴 Processing expired members...');
        $bar = $this->output->createProgressBar($expiredMembers->count());
        $bar->start();

        $disabledCount = 0;

        foreach ($expiredMembers as $member) {
            try {
                if ($this->option('sync-queue')) {
                    KickMikrotikUserJob::dispatch($member, disableUser: true);
                } else {
                    $mikrotikService->disableAndKickUser($member);
                }
                $disabledCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('network:sync-all - Failed to disable member', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Sync completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Members Enabled', $enabledCount],
                ['Members Disabled', $disabledCount],
                ['Failed Operations', $failedCount],
            ]
        );

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display members in a table.
     */
    protected function displayMembersTable(string $title, $members): void
    {
        $this->info($title);

        if ($members->isEmpty()) {
            $this->line('   No members found.');
            $this->newLine();

            return;
        }

        $tableData = $members->map(fn ($member) => [
            'ID' => $member->id,
            'Code' => $member->member_code,
            'Name' => $member->user?->name ?? 'N/A',
            'Phone' => $member->user?->phone ?? 'N/A',
        ])->toArray();

        $this->table(['ID', 'Code', 'Name', 'Phone'], $tableData);
        $this->newLine();
    }
}
