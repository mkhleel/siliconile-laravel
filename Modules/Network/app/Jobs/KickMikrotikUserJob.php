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
use Modules\Network\Services\MikrotikService;

/**
 * Job to kick (terminate session) and disable a member on Mikrotik.
 *
 * Used when membership expires or is cancelled.
 */
class KickMikrotikUserJob implements ShouldQueue
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
     *
     * @param  bool  $disableUser  Whether to also disable the user account
     */
    public function __construct(
        public Member $member,
        public bool $disableUser = true,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MikrotikService $mikrotikService): void
    {
        if (! $mikrotikService->isAvailable()) {
            Log::info('KickMikrotikUserJob: Network module is not available, skipping', [
                'member_id' => $this->member->id,
            ]);

            return;
        }

        try {
            if ($this->disableUser) {
                // Kick and disable
                $success = $mikrotikService->disableAndKickUser($this->member);
            } else {
                // Just kick active sessions
                $success = $mikrotikService->kickUser($this->member);
            }

            if ($success) {
                Log::info('KickMikrotikUserJob: Successfully processed', [
                    'member_id' => $this->member->id,
                    'disabled' => $this->disableUser,
                ]);
            } else {
                Log::warning('KickMikrotikUserJob: Operation returned false', [
                    'member_id' => $this->member->id,
                ]);
            }

        } catch (MikrotikConnectionException $e) {
            Log::error('KickMikrotikUserJob: Connection failed, will retry', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Release back to queue for retry
            $this->release($this->backoff);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('KickMikrotikUserJob: Job failed permanently', [
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
            'kick',
            "member:{$this->member->id}",
        ];
    }
}
