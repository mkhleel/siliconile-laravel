<?php

declare(strict_types=1);

namespace Modules\Billing\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Services\InvoiceService;
use Modules\Payment\Events\PaymentCompleted;

/**
 * UpdateInvoiceOnPayment Listener
 *
 * Listens to PaymentCompleted events from the Payment module
 * and marks the associated invoice as paid.
 */
class UpdateInvoiceOnPayment
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        // Check if the payment is for an invoice
        if ($payment->payable_type !== Invoice::class) {
            return;
        }

        $invoice = $payment->payable;

        if (!$invoice instanceof Invoice) {
            Log::warning('PaymentCompleted event payable is not an Invoice', [
                'payment_id' => $payment->id,
                'payable_type' => $payment->payable_type,
                'payable_id' => $payment->payable_id,
            ]);
            return;
        }

        // Check if the invoice can be paid
        if (!$invoice->canBePaid()) {
            Log::warning('Invoice cannot be marked as paid', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'current_status' => $invoice->status->value,
            ]);
            return;
        }

        try {
            $this->invoiceService->markAsPaid(
                $invoice,
                $payment->reference,
                $payment->gateway
            );

            Log::info('Invoice marked as paid via PaymentCompleted event', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'payment_reference' => $payment->reference,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark invoice as paid', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
