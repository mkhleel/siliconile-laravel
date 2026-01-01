<?php

declare(strict_types=1);

namespace Modules\Membership\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\PushoverService;
use Modules\Membership\Events\MemberCreated;

class SendMemberCreatedNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly PushoverService $pushoverService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(MemberCreated $event): void
    {
        // Skip if Pushover is not configured
        if (! $this->pushoverService->isConfigured()) {
            Log::info('Pushover not configured, skipping member notification');

            return;
        }

        $member = $event->member;
        $user = $member->user;

        // Build notification message
        $message = "New membership application received!\n\n";
        $message .= "Member: {$user->name}\n";
        $message .= "Email: {$user->email}\n";
        $message .= "Type: {$member->member_type->label()}\n";
        $message .= "Code: {$member->member_code}";

        if ($member->company_name) {
            $message .= "\nCompany: {$member->company_name}";
        }

        // Send high priority notification
        $result = $this->pushoverService->sendHighPriority(
            message: $message,
            title: '🎉 New Member Application',
            url: route('filament.admin.resources.members.edit', ['record' => $member->id])
        );

        if ($result['status'] === 1) {
            Log::info('Pushover notification sent for new member', [
                'member_id' => $member->id,
                'member_code' => $member->member_code,
            ]);
        } else {
            Log::warning('Failed to send Pushover notification for new member', [
                'member_id' => $member->id,
                'errors' => $result['errors'] ?? [],
            ]);
        }
    }
}
