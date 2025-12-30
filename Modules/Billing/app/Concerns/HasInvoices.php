<?php

declare(strict_types=1);

namespace Modules\Billing\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Billing\Models\Invoice;

/**
 * HasInvoices Trait
 *
 * Add this trait to any model that can be billed (Member, User, etc.)
 * to enable invoice relationships.
 */
trait HasInvoices
{
    /**
     * Get all invoices for this billable entity.
     */
    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'billable');
    }

    /**
     * Get unpaid invoices for this billable entity.
     */
    public function unpaidInvoices(): MorphMany
    {
        return $this->invoices()->unpaid();
    }

    /**
     * Get overdue invoices for this billable entity.
     */
    public function overdueInvoices(): MorphMany
    {
        return $this->invoices()->overdue();
    }

    /**
     * Get the total outstanding balance.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->unpaidInvoices()->sum('total');
    }

    /**
     * Check if the billable has any overdue invoices.
     */
    public function hasOverdueInvoices(): bool
    {
        return $this->overdueInvoices()->exists();
    }

    /**
     * Get the default billing email.
     */
    public function getBillingEmailAttribute(): ?string
    {
        return $this->email ?? $this->user?->email ?? null;
    }

    /**
     * Get the default billing name.
     */
    public function getBillingNameAttribute(): string
    {
        return $this->name ?? $this->user?->name ?? 'Customer';
    }
}
