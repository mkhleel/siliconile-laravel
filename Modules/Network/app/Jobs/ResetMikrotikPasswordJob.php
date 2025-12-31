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
use Modules\Network\DTOs\HotspotUserDTO;
use Modules\Network\Exceptions\MikrotikOperationException;
use Modules\Network\Services\MikrotikService;

/**
 * Job to reset a member's WiFi password.
 */
class ResetMikrotikPasswordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 15;

    /**
     * Result of the password reset operation.
     */
    public ?HotspotUserDTO $result = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Member $member,
        public ?string $newPassword = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MikrotikService $mikrotikService): void
    {
        if (! $mikrotikService->isAvailable()) {
            Log::info('ResetMikrotikPasswordJob: Network module is not available, skipping', [
                'member_id' => $this->member->id,
            ]);

            return;
        }

        try {
            $this->result = $mikrotikService->resetPassword($this->member, $this->newPassword);

            Log::info('ResetMikrotikPasswordJob: Successfully reset password', [
                'member_id' => $this->member->id,
                'username' => $this->result->username,
            ]);

            // TODO: Optionally notify the member of their new password
            // You can dispatch a notification job here

        } catch (MikrotikOperationException $e) {
            Log::error('ResetMikrotikPasswordJob: Failed to reset password', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('ResetMikrotikPasswordJob: Job failed permanently', [
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
            'password-reset',
            "member:{$this->member->id}",
        ];
    }
}
