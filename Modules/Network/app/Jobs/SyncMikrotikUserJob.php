<?php

declare(strict_types=1);

namespace Modules\Network\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Models\Member;
use Modules\Network\Exceptions\MikrotikConnectionException;
use Modules\Network\Exceptions\MikrotikOperationException;
use Modules\Network\Services\MikrotikService;

/**
 * Job to sync a member to Mikrotik hotspot.
 *
 * Creates or updates the user on the router.
 */
class SyncMikrotikUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Member $member,
        public ?string $password = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MikrotikService $mikrotikService): void
    {
        if (! $mikrotikService->isAvailable()) {
            Log::info('SyncMikrotikUserJob: Network module is not available, skipping', [
                'member_id' => $this->member->id,
            ]);

            return;
        }

        try {
            $result = $mikrotikService->syncUser($this->member, $this->password);

            Log::info('SyncMikrotikUserJob: Successfully synced user', [
                'member_id' => $this->member->id,
                'username' => $result->username,
            ]);

        } catch (MikrotikConnectionException $e) {
            Log::error('SyncMikrotikUserJob: Connection failed, will retry', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Release back to queue for retry
            $this->release($this->backoff);

        } catch (MikrotikOperationException $e) {
            Log::error('SyncMikrotikUserJob: Operation failed', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
            ]);

            // Don't retry on operation failures
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('SyncMikrotikUserJob: Job failed permanently', [
            'member_id' => $this->member->id,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'network',
            'mikrotik',
            'sync',
            "member:{$this->member->id}",
        ];
    }
}
