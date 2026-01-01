<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Services\PushoverService;

class TestPushoverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pushover:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Pushover notification configuration';

    /**
     * Execute the console command.
     */
    public function handle(PushoverService $pushoverService): int
    {
        $this->info('Testing Pushover configuration...');

        if (! $pushoverService->isConfigured()) {
            $this->error('❌ Pushover is not configured!');
            $this->newLine();
            $this->warn('Please add the following to your .env file:');
            $this->line('PUSHOVER_API_TOKEN=your_app_token_here');
            $this->line('PUSHOVER_USER_KEY=your_user_key_here');
            $this->newLine();
            $this->info('Get your credentials from https://pushover.net/');

            return self::FAILURE;
        }

        $this->info('✓ Configuration found');
        $this->info('Sending test notification...');

        $result = $pushoverService->testConnection();

        if ($result['status'] === 1) {
            $this->newLine();
            $this->info('✅ Success! Test notification sent.');
            $this->info('Check your Pushover app for the notification.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('❌ Failed to send notification');

        if (isset($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->error("  • {$error}");
            }
        }

        return self::FAILURE;
    }
}
