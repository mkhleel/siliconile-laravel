<?php

declare(strict_types=1);

namespace Modules\Billing\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Models\Invoice;

/**
 * InvoiceObserver - Handles invoice model lifecycle events.
 *
 * Key responsibilities:
 * - Auto-check for overdue status on retrieval
 * - Logging for audit trails
 */
class InvoiceObserver
{
    /**
     * Handle the Invoice "retrieved" event.
     *
     * Auto-update status to overdue if past due date.
     */
    public function retrieved(Invoice $invoice): void
    {
        // Auto-mark as overdue if conditions are met
        if ($this->shouldMarkAsOverdue($invoice)) {
            $invoice->status = InvoiceStatus::OVERDUE;
            $invoice->saveQuietly(); // Save without triggering observers again
        }
    }

    /**
     * Handle the Invoice "creating" event.
     */
    public function creating(Invoice $invoice): void
    {
        // Ensure default status
        $invoice->status ??= InvoiceStatus::DRAFT;

        // Set default currency
        $invoice->currency ??= config('billing.default_currency', 'EGP');

        // Set default tax rate
        $invoice->tax_rate ??= config('billing.default_tax_rate', 15.00);
    }

    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        Log::info('Invoice created', [
            'invoice_id' => $invoice->id,
            'billable_type' => $invoice->billable_type,
            'billable_id' => $invoice->billable_id,
            'status' => $invoice->status->value,
        ]);
    }

    /**
     * Handle the Invoice "updating" event.
     */
    public function updating(Invoice $invoice): void
    {
        // Track status changes for logging
        if ($invoice->isDirty('status')) {
            $invoice->setAttribute('_previous_status', $invoice->getOriginal('status'));
        }
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Log status changes
        if ($invoice->wasChanged('status')) {
            $previousStatus = $invoice->getAttribute('_previous_status');

            Log::info('Invoice status changed', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'from_status' => $previousStatus instanceof InvoiceStatus
                    ? $previousStatus->value
                    : $previousStatus,
                'to_status' => $invoice->status->value,
            ]);
        }
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        Log::info('Invoice deleted', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'status' => $invoice->status->value,
        ]);
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        Log::info('Invoice restored', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
        ]);
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        Log::warning('Invoice permanently deleted', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
        ]);
    }

    /**
     * Check if invoice should be marked as overdue.
     */
    protected function shouldMarkAsOverdue(Invoice $invoice): bool
    {
        // Only check sent invoices
        if ($invoice->status !== InvoiceStatus::SENT) {
            return false;
        }

        // Must have a due date
        if (! $invoice->due_date) {
            return false;
        }

        // Check if past due
        return $invoice->due_date->isPast();
    }
}
