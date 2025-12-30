<?php

declare(strict_types=1);

namespace Modules\Billing\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\InvoiceItem;

/**
 * CanBeInvoiced Trait
 *
 * Add this trait to models that can be the origin of an invoice
 * (Subscription, Booking, etc.)
 */
trait CanBeInvoiced
{
    /**
     * Get all invoices where this entity is the origin.
     */
    public function originInvoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'origin');
    }

    /**
     * Get all invoice items where this entity is the origin.
     */
    public function invoiceItems(): MorphMany
    {
        return $this->morphMany(InvoiceItem::class, 'origin');
    }

    /**
     * Check if this entity has been invoiced.
     */
    public function hasBeenInvoiced(): bool
    {
        return $this->originInvoices()->exists()
            || $this->invoiceItems()->exists();
    }

    /**
     * Get the latest invoice for this entity.
     */
    public function getLatestInvoiceAttribute(): ?Invoice
    {
        return $this->originInvoices()->latest()->first();
    }

    /**
     * Check if any invoice for this entity has been paid.
     */
    public function isPaid(): bool
    {
        return $this->originInvoices()
            ->where('status', \Modules\Billing\Enums\InvoiceStatus::PAID)
            ->exists();
    }
}
