<?php

declare(strict_types=1);

namespace Modules\Events\Jobs;

use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Events\Models\Attendee;

/**
 * GenerateTicketPdfJob
 *
 * Generates a PDF ticket for an attendee with QR code.
 */
class GenerateTicketPdfJob implements ShouldQueue
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
        $attendee = $this->attendee->load(['event', 'ticketType']);

        // Generate QR code SVG
        $qrCode = $this->generateQrCodeSvg();

        // Generate PDF
        $pdfPath = $this->generatePdf($qrCode);

        // Update attendee with PDF path
        $attendee->update([
            'ticket_pdf_path' => $pdfPath,
        ]);

        Log::info('Ticket PDF generated', [
            'attendee_id' => $attendee->id,
            'reference' => $attendee->reference_no,
            'pdf_path' => $pdfPath,
        ]);
    }

    /**
     * Generate QR code as SVG string.
     */
    private function generateQrCodeSvg(): string
    {
        $qrContent = $this->attendee->qr_code_content;

        $renderer = new ImageRenderer(
            new RendererStyle(150),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrContent, Encoder::DEFAULT_BYTE_MODE_ECODING);
    }

    /**
     * Generate PDF ticket and return storage path.
     */
    private function generatePdf(string $qrCode): string
    {
        $attendee = $this->attendee;
        $event = $attendee->event;
        $ticketType = $attendee->ticketType;

        $directory = "events/tickets/{$event->id}";
        $filename = "{$attendee->reference_no}.pdf";
        $fullPath = "{$directory}/{$filename}";

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);

        // Get absolute path for PDF generation
        $absolutePath = Storage::disk('public')->path($fullPath);

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView('events::tickets.pdf', [
            'attendee' => $attendee,
            'event' => $event,
            'ticketType' => $ticketType,
            'qrCode' => base64_encode($qrCode),
        ])
            ->setPaper('a5', 'landscape');

        $pdf->save($absolutePath);

        return $fullPath;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to generate ticket PDF', [
            'attendee_id' => $this->attendee->id,
            'reference' => $this->attendee->reference_no,
            'error' => $exception->getMessage(),
        ]);
    }
}
