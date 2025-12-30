<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Events\InvoiceCreated;
use Modules\Billing\Events\InvoiceFinalized;
use Modules\Billing\Events\InvoicePaid;
use Modules\Billing\Events\InvoiceSent;
use Modules\Billing\Events\InvoiceVoided;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\InvoiceItem;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Subscription;
use Modules\SpaceBooking\Models\Booking;

/**
 * InvoiceService - Handles all invoice business logic.
 *
 * This service is the central point for creating, finalizing, and managing invoices.
 * It supports polymorphic billable entities and integrates with the Payment module.
 */
class InvoiceService
{
    /**
     * Default VAT rate (Egypt standard).
     */
    private const DEFAULT_TAX_RATE = 14.00;

    /**
     * Default payment terms in days.
     */
    private const DEFAULT_PAYMENT_TERMS_DAYS = 14;

    /**
     * Create a new draft invoice.
     *
     * @param Model $billable The entity being billed (Member, User)
     * @param array<string, mixed> $data Additional invoice data
     * @return Invoice
     */
    public function create(Model $billable, array $data = []): Invoice
    {
        $invoice = new Invoice();
        $invoice->billable()->associate($billable);
        $invoice->status = InvoiceStatus::DRAFT;
        $invoice->currency = $data['currency'] ?? config('billing.default_currency', 'EGP');
        $invoice->tax_rate = $data['tax_rate'] ?? self::DEFAULT_TAX_RATE;
        $invoice->issue_date = $data['issue_date'] ?? now();
        $invoice->due_date = $data['due_date'] ?? now()->addDays(self::DEFAULT_PAYMENT_TERMS_DAYS);
        $invoice->notes = $data['notes'] ?? null;
        $invoice->terms = $data['terms'] ?? config('billing.default_invoice_terms');
        $invoice->metadata = $data['metadata'] ?? [];

        // Snapshot billing details from billable
        $invoice->billing_details = $this->extractBillingDetails($billable);

        // Associate origin if provided
        if (isset($data['origin']) && $data['origin'] instanceof Model) {
            $invoice->origin()->associate($data['origin']);
        }

        $invoice->save();

        event(new InvoiceCreated($invoice));

        return $invoice;
    }

    /**
     * Create an invoice from a Subscription.
     *
     * @param Subscription $subscription
     * @param array<string, mixed> $options Additional options
     * @return Invoice
     */
    public function createFromSubscription(Subscription $subscription, array $options = []): Invoice
    {
        return DB::transaction(function () use ($subscription, $options) {
            $member = $subscription->member;
            $plan = $subscription->plan;

            // Create the invoice
            $invoice = $this->create($member, [
                'origin' => $subscription,
                'currency' => $subscription->currency ?? 'SAR',
                'notes' => $options['notes'] ?? null,
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'billing_period_start' => $subscription->start_date?->toDateString(),
                    'billing_period_end' => $subscription->end_date?->toDateString(),
                ],
            ]);

            // Add subscription as a line item
            $this->addItem($invoice, [
                'description' => sprintf(
                    '%s - %s (%s to %s)',
                    $plan->name ?? 'Membership Plan',
                    $plan->type?->getLabel() ?? 'Subscription',
                    $subscription->start_date?->format('M d, Y') ?? 'N/A',
                    $subscription->end_date?->format('M d, Y') ?? 'N/A'
                ),
                'quantity' => 1,
                'unit_price' => $subscription->price_at_subscription ?? $plan->price,
                'origin' => $subscription,
            ]);

            // Recalculate totals
            $invoice->calculateTotals();
            $invoice->save();

            Log::info('Invoice created from subscription', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'member_id' => $member->id,
                'total' => $invoice->total,
            ]);

            return $invoice;
        });
    }

    /**
     * Create an invoice from a Booking.
     *
     * @param Booking $booking
     * @param array<string, mixed> $options Additional options
     * @return Invoice
     */
    public function createFromBooking(Booking $booking, array $options = []): Invoice
    {
        return DB::transaction(function () use ($booking, $options) {
            // Get the billable entity (Member or User from bookable)
            $billable = $booking->bookable;

            // Determine the appropriate billable - prefer Member if available
            if ($billable instanceof Member) {
                $invoiceBillable = $billable;
            } elseif (method_exists($billable, 'member') && $billable->member) {
                $invoiceBillable = $billable->member;
            } else {
                $invoiceBillable = $billable;
            }

            $resource = $booking->spaceResource;

            // Create the invoice
            $invoice = $this->create($invoiceBillable, [
                'origin' => $booking,
                'currency' => $booking->currency ?? 'SAR',
                'notes' => $options['notes'] ?? null,
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'resource_id' => $resource?->id,
                    'start_time' => $booking->start_time?->toIso8601String(),
                    'end_time' => $booking->end_time?->toIso8601String(),
                ],
            ]);

            // Add booking as a line item
            $description = sprintf(
                '%s - %s (%s, %s - %s)',
                $resource?->name ?? 'Space Booking',
                $booking->booking_code,
                $booking->start_time?->format('M d, Y') ?? 'N/A',
                $booking->start_time?->format('H:i') ?? '',
                $booking->end_time?->format('H:i') ?? ''
            );

            $this->addItem($invoice, [
                'description' => $description,
                'quantity' => $booking->quantity ?? 1,
                'unit_price' => $booking->unit_price ?? $booking->total_price,
                'discount_amount' => $booking->discount_amount ?? 0,
                'origin' => $booking,
            ]);

            // Recalculate totals
            $invoice->calculateTotals();
            $invoice->save();

            Log::info('Invoice created from booking', [
                'invoice_id' => $invoice->id,
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'total' => $invoice->total,
            ]);

            return $invoice;
        });
    }

    /**
     * Create a manual invoice with custom items.
     *
     * @param Model $billable The entity being billed
     * @param array<array<string, mixed>> $items Line items
     * @param array<string, mixed> $options Additional options
     * @return Invoice
     */
    public function createManual(Model $billable, array $items, array $options = []): Invoice
    {
        return DB::transaction(function () use ($billable, $items, $options) {
            $invoice = $this->create($billable, $options);

            foreach ($items as $itemData) {
                $this->addItem($invoice, $itemData);
            }

            // Recalculate totals
            $invoice->calculateTotals();
            $invoice->save();

            return $invoice;
        });
    }

    /**
     * Add a line item to an invoice.
     *
     * @param Invoice $invoice
     * @param array<string, mixed> $data Item data
     * @return InvoiceItem
     *
     * @throws \RuntimeException If invoice is not editable
     */
    public function addItem(Invoice $invoice, array $data): InvoiceItem
    {
        if (!$invoice->isEditable()) {
            throw new \RuntimeException('Cannot add items to a finalized invoice.');
        }

        $item = new InvoiceItem();
        $item->invoice_id = $invoice->id;
        $item->description = $data['description'];
        $item->quantity = $data['quantity'] ?? 1;
        $item->unit_price = $data['unit_price'];
        $item->discount_amount = $data['discount_amount'] ?? 0;
        $item->tax_amount = $data['tax_amount'] ?? 0;
        $item->metadata = $data['metadata'] ?? null;
        $item->sort_order = $data['sort_order'] ?? ($invoice->items()->max('sort_order') ?? 0) + 1;

        // Calculate total
        $item->total = ($item->unit_price * $item->quantity) - $item->discount_amount;

        // Associate origin if provided
        if (isset($data['origin']) && $data['origin'] instanceof Model) {
            $item->origin()->associate($data['origin']);
        }

        $item->save();

        return $item;
    }

    /**
     * Finalize an invoice - generates number and locks it.
     *
     * @param Invoice $invoice
     * @return Invoice
     *
     * @throws \RuntimeException If invoice cannot be finalized
     */
    public function finalize(Invoice $invoice): Invoice
    {
        if (!$invoice->isEditable()) {
            throw new \RuntimeException('Invoice is already finalized.');
        }

        if ($invoice->items()->count() === 0) {
            throw new \RuntimeException('Cannot finalize an invoice without items.');
        }

        return DB::transaction(function () use ($invoice) {
            // Recalculate totals before finalizing
            $invoice->calculateTotals();

            // Generate invoice number
            $invoice->number = $this->generateInvoiceNumber();
            $invoice->status = InvoiceStatus::SENT;
            $invoice->issue_date = $invoice->issue_date ?? now();
            $invoice->sent_at = now();

            // Ensure due date is set
            if (!$invoice->due_date) {
                $invoice->due_date = now()->addDays(self::DEFAULT_PAYMENT_TERMS_DAYS);
            }

            $invoice->save();

            event(new InvoiceFinalized($invoice));
            event(new InvoiceSent($invoice));

            Log::info('Invoice finalized', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'total' => $invoice->total,
            ]);

            return $invoice;
        });
    }

    /**
     * Mark an invoice as paid.
     *
     * @param Invoice $invoice
     * @param string|null $transactionReference Payment transaction reference
     * @param string|null $paymentMethod Payment method used
     * @return Invoice
     *
     * @throws \RuntimeException If invoice cannot be marked as paid
     */
    public function markAsPaid(Invoice $invoice, ?string $transactionReference = null, ?string $paymentMethod = null): Invoice
    {
        if (!$invoice->canBePaid()) {
            throw new \RuntimeException("Invoice #{$invoice->display_number} cannot be marked as paid in its current status.");
        }

        return DB::transaction(function () use ($invoice, $transactionReference, $paymentMethod) {
            $invoice->status = InvoiceStatus::PAID;
            $invoice->paid_at = now();
            $invoice->payment_reference = $transactionReference;
            $invoice->payment_method = $paymentMethod;
            $invoice->save();

            event(new InvoicePaid($invoice, $transactionReference));

            Log::info('Invoice marked as paid', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'transaction_reference' => $transactionReference,
                'payment_method' => $paymentMethod,
            ]);

            return $invoice;
        });
    }

    /**
     * Void an invoice.
     *
     * @param Invoice $invoice
     * @param string|null $reason Reason for voiding
     * @return Invoice
     *
     * @throws \RuntimeException If invoice cannot be voided
     */
    public function void(Invoice $invoice, ?string $reason = null): Invoice
    {
        if (!$invoice->status->canBeVoided()) {
            throw new \RuntimeException("Invoice #{$invoice->display_number} cannot be voided.");
        }

        return DB::transaction(function () use ($invoice, $reason) {
            $invoice->status = InvoiceStatus::VOID;
            $invoice->voided_at = now();

            if ($reason) {
                $metadata = $invoice->metadata ?? [];
                $metadata['void_reason'] = $reason;
                $invoice->metadata = $metadata;
            }

            $invoice->save();

            event(new InvoiceVoided($invoice, $reason));

            Log::info('Invoice voided', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'reason' => $reason,
            ]);

            return $invoice;
        });
    }

    /**
     * Mark overdue invoices.
     *
     * This method should be called daily via scheduler.
     *
     * @return int Number of invoices marked as overdue
     */
    public function markOverdueInvoices(): int
    {
        $count = Invoice::query()
            ->where('status', InvoiceStatus::SENT)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->update(['status' => InvoiceStatus::OVERDUE]);

        if ($count > 0) {
            Log::info("Marked {$count} invoices as overdue");
        }

        return $count;
    }

    /**
     * Apply a discount to an invoice.
     *
     * @param Invoice $invoice
     * @param float $amount Discount amount
     * @param string|null $description Discount description
     * @return Invoice
     *
     * @throws \RuntimeException If invoice is not editable
     */
    public function applyDiscount(Invoice $invoice, float $amount, ?string $description = null): Invoice
    {
        if (!$invoice->isEditable()) {
            throw new \RuntimeException('Cannot apply discount to a finalized invoice.');
        }

        $invoice->discount_amount = $amount;
        $invoice->discount_description = $description;
        $invoice->calculateTotals();
        $invoice->save();

        return $invoice;
    }

    /**
     * Duplicate an invoice (creates a new draft copy).
     *
     * @param Invoice $invoice
     * @return Invoice
     */
    public function duplicate(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $newInvoice = $invoice->replicate([
                'number',
                'status',
                'issue_date',
                'due_date',
                'paid_at',
                'sent_at',
                'voided_at',
                'payment_reference',
                'payment_method',
                'pdf_path',
                'pdf_generated_at',
            ]);

            $newInvoice->status = InvoiceStatus::DRAFT;
            $newInvoice->issue_date = now();
            $newInvoice->due_date = now()->addDays(self::DEFAULT_PAYMENT_TERMS_DAYS);
            $newInvoice->save();

            // Duplicate items
            foreach ($invoice->items as $item) {
                $newItem = $item->replicate();
                $newItem->invoice_id = $newInvoice->id;
                $newItem->save();
            }

            return $newInvoice;
        });
    }

    /**
     * Generate a unique sequential invoice number.
     *
     * Format: INV-YYYY-NNNN (e.g., INV-2025-0001)
     *
     * @return string
     */
    protected function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = config('billing.invoice_number_prefix', 'INV');

        // Get the last invoice number for this year
        $lastInvoice = Invoice::query()
            ->whereNotNull('number')
            ->whereYear('issue_date', $year)
            ->orderByDesc('number')
            ->lockForUpdate()
            ->first();

        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    /**
     * Extract billing details from a billable entity.
     *
     * @param Model $billable
     * @return array<string, mixed>
     */
    protected function extractBillingDetails(Model $billable): array
    {
        $details = [
            'billable_type' => get_class($billable),
            'billable_id' => $billable->getKey(),
        ];

        // Extract common fields
        if (isset($billable->name)) {
            $details['name'] = $billable->name;
        } elseif (isset($billable->user)) {
            $details['name'] = $billable->user->name ?? null;
        }

        if (isset($billable->email)) {
            $details['email'] = $billable->email;
        } elseif (isset($billable->user)) {
            $details['email'] = $billable->user->email ?? null;
        }

        // Member-specific fields
        if ($billable instanceof Member) {
            $details['member_code'] = $billable->member_code;
            $details['company_name'] = $billable->company_name;
            $details['company_vat_number'] = $billable->company_vat_number;
            $details['company_address'] = $billable->company_address;
        }

        return array_filter($details);
    }
}
