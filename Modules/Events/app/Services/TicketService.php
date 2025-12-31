<?php

declare(strict_types=1);

namespace Modules\Events\Services;

use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Events\Events\TicketIssued;
use Modules\Events\Jobs\GenerateTicketPdfJob;
use Modules\Events\Jobs\SendTicketEmailJob;
use Modules\Events\Models\Attendee;

/**
 * TicketService
 *
 * Handles ticket generation, QR codes, PDF creation, and email delivery.
 */
class TicketService
{
    /**
     * Issue a ticket to an attendee.
     *
     * Generates QR code, creates PDF, and sends email.
     */
    public function issueTicket(Attendee $attendee): void
    {
        // Dispatch jobs to handle PDF generation and email sending asynchronously
        GenerateTicketPdfJob::dispatch($attendee)
            ->chain([
                new SendTicketEmailJob($attendee),
            ]);

        // Fire event for other modules to listen
        event(new TicketIssued($attendee));

        Log::info('Ticket issue process initiated', [
            'attendee_id' => $attendee->id,
            'reference' => $attendee->reference_no,
            'event_id' => $attendee->event_id,
        ]);
    }

    /**
     * Generate QR code image for an attendee.
     *
     * @return string Base64 encoded QR code SVG
     */
    public function generateQrCode(Attendee $attendee): string
    {
        $qrContent = $attendee->qr_code_content;

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $svgContent = $writer->writeString($qrContent, Encoder::DEFAULT_BYTE_MODE_ECODING);

        return base64_encode($svgContent);
    }

    /**
     * Generate QR code SVG as raw string for direct embedding.
     */
    public function generateQrCodeSvg(Attendee $attendee): string
    {
        $qrContent = $attendee->qr_code_content;

        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrContent, Encoder::DEFAULT_BYTE_MODE_ECODING);
    }

    /**
     * Generate QR code and save to storage.
     *
     * @return string Path to saved QR code SVG
     */
    public function generateAndSaveQrCode(Attendee $attendee): string
    {
        $svgContent = $this->generateQrCodeSvg($attendee);

        $path = "events/qrcodes/{$attendee->event_id}/{$attendee->reference_no}.svg";
        Storage::disk('public')->put($path, $svgContent);

        return $path;
    }

    /**
     * Validate a QR code scan and return attendee if valid.
     */
    public function validateQrCode(string $qrData, int $eventId): ?Attendee
    {
        $attendee = Attendee::findByQrCode($qrData);

        if (! $attendee) {
            return null;
        }

        // Verify it's for the correct event
        if ($attendee->event_id !== $eventId) {
            return null;
        }

        return $attendee;
    }

    /**
     * Perform check-in from QR scan.
     *
     * @return array{success: bool, message: string, attendee: ?Attendee}
     */
    public function checkInFromQr(string $qrData, int $eventId, ?int $checkedInBy = null): array
    {
        $attendee = $this->validateQrCode($qrData, $eventId);

        if (! $attendee) {
            return [
                'success' => false,
                'message' => __('Invalid ticket or wrong event.'),
                'attendee' => null,
            ];
        }

        if ($attendee->has_checked_in) {
            return [
                'success' => false,
                'message' => __('Already checked in at :time', [
                    'time' => $attendee->checked_in_at->format('H:i'),
                ]),
                'attendee' => $attendee,
            ];
        }

        if (! $attendee->can_check_in) {
            return [
                'success' => false,
                'message' => __('Ticket status does not allow check-in: :status', [
                    'status' => $attendee->status->getLabel(),
                ]),
                'attendee' => $attendee,
            ];
        }

        $attendee->checkIn($checkedInBy, 'qr_scan');

        return [
            'success' => true,
            'message' => __('Successfully checked in!'),
            'attendee' => $attendee->fresh(),
        ];
    }

    /**
     * Perform manual check-in by reference number.
     *
     * @return array{success: bool, message: string, attendee: ?Attendee}
     */
    public function checkInByReference(string $reference, int $eventId, ?int $checkedInBy = null): array
    {
        $attendee = Attendee::where('reference_no', $reference)
            ->where('event_id', $eventId)
            ->first();

        if (! $attendee) {
            return [
                'success' => false,
                'message' => __('Ticket not found.'),
                'attendee' => null,
            ];
        }

        if ($attendee->has_checked_in) {
            return [
                'success' => false,
                'message' => __('Already checked in at :time', [
                    'time' => $attendee->checked_in_at->format('H:i'),
                ]),
                'attendee' => $attendee,
            ];
        }

        if (! $attendee->can_check_in) {
            return [
                'success' => false,
                'message' => __('Ticket status does not allow check-in: :status', [
                    'status' => $attendee->status->getLabel(),
                ]),
                'attendee' => $attendee,
            ];
        }

        $attendee->checkIn($checkedInBy, 'manual');

        return [
            'success' => true,
            'message' => __('Successfully checked in!'),
            'attendee' => $attendee->fresh(),
        ];
    }

    /**
     * Resend ticket email to attendee.
     */
    public function resendTicketEmail(Attendee $attendee): void
    {
        // Regenerate PDF if needed
        if (! $attendee->ticket_pdf_path || ! Storage::exists($attendee->ticket_pdf_path)) {
            GenerateTicketPdfJob::dispatchSync($attendee);
            $attendee->refresh();
        }

        SendTicketEmailJob::dispatch($attendee);
    }

    /**
     * Bulk check-in multiple attendees.
     *
     * @param  array<string>  $references  Array of reference numbers
     * @return array{checked_in: int, failed: int, errors: array<string, string>}
     */
    public function bulkCheckIn(array $references, int $eventId, ?int $checkedInBy = null): array
    {
        $checkedIn = 0;
        $failed = 0;
        $errors = [];

        foreach ($references as $reference) {
            $result = $this->checkInByReference($reference, $eventId, $checkedInBy);

            if ($result['success']) {
                $checkedIn++;
            } else {
                $failed++;
                $errors[$reference] = $result['message'];
            }
        }

        return [
            'checked_in' => $checkedIn,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
