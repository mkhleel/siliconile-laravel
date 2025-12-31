<?php

declare(strict_types=1);

namespace Modules\Events\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Events\Models\Attendee;

/**
 * TicketMail
 *
 * Email sent to attendees with their event ticket.
 */
class TicketMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Attendee $attendee
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $event = $this->attendee->event;

        return new Envelope(
            subject: __('Your Ticket for :event', ['event' => $event->title]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'events::emails.ticket',
            with: [
                'attendee' => $this->attendee,
                'event' => $this->attendee->event,
                'ticketType' => $this->attendee->ticketType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->attendee->ticket_pdf_path) {
            $pdfPath = Storage::disk('public')->path($this->attendee->ticket_pdf_path);

            if (file_exists($pdfPath)) {
                $attachments[] = Attachment::fromPath($pdfPath)
                    ->as("ticket-{$this->attendee->reference_no}.pdf")
                    ->withMime('application/pdf');
            }
        }

        return $attachments;
    }
}
