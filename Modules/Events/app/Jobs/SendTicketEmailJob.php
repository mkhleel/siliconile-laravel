<?php

declare(strict_types=1);

namespace Modules\Events\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Events\Mail\TicketMail;
use Modules\Events\Models\Attendee;

/**
 * SendTicketEmailJob
 *
 * Sends the ticket email with PDF attachment to the attendee.
 */
class SendTicketEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Attendee $attendee
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $attendee = $this->attendee->fresh(['event', 'ticketType']);

        if (! $attendee) {
            Log::warning('Attendee not found for ticket email', [
                'attendee_id' => $this->attendee->id,
            ]);

            return;
        }

        $email = $attendee->email;

        if (! $email) {
            Log::warning('No email address for attendee', [
                'attendee_id' => $attendee->id,
            ]);

            return;
        }

        // Send the email
        Mail::to($email)->send(new TicketMail($attendee));

        // Mark ticket as sent
        $attendee->markTicketSent($attendee->ticket_pdf_path);

        Log::info('Ticket email sent', [
            'attendee_id' => $attendee->id,
            'reference' => $attendee->reference_no,
            'email' => $email,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send ticket email', [
            'attendee_id' => $this->attendee->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
