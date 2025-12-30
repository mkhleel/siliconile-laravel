<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Observers\InvoiceObserver;

/**
 * Invoice Model - Represents a tax-compliant invoice for billing.
 *
 * Supports polymorphic billable (Member/User) and origin (Subscription/Booking) relationships.
 *
 * @property int $id
 * @property string|null $number
 * @property string $billable_type
 * @property int $billable_id
 * @property string|null $origin_type
 * @property int|null $origin_id
 * @property InvoiceStatus $status
 * @property \Carbon\Carbon|null $issue_date
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $voided_at
 * @property string $currency
 * @property string $subtotal
 * @property string $discount_amount
 * @property string|null $discount_description
 * @property string $tax_rate
 * @property string $tax_amount
 * @property string $total
 * @property string|null $payment_reference
 * @property string|null $payment_method
 * @property array|null $billing_details
 * @property string|null $notes
 * @property string|null $terms
 * @property array|null $metadata
 * @property string|null $pdf_path
 * @property \Carbon\Carbon|null $pdf_generated_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Model $billable
 * @property-read Model|null $origin
 * @property-read Collection<InvoiceItem> $items
 */
#[ObservedBy([InvoiceObserver::class])]
class Invoice extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'number',
        'billable_type',
        'billable_id',
        'origin_type',
        'origin_id',
        'status',
        'issue_date',
        'due_date',
        'paid_at',
        'sent_at',
        'voided_at',
        'currency',
        'subtotal',
        'discount_amount',
        'discount_description',
        'tax_rate',
        'tax_amount',
        'total',
        'payment_reference',
        'payment_method',
        'billing_details',
        'notes',
        'terms',
        'metadata',
        'pdf_path',
        'pdf_generated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'sent_at' => 'datetime',
            'voided_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'billing_details' => 'array',
            'metadata' => 'array',
            'pdf_generated_at' => 'datetime',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the billable entity (Member, User, etc.).
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the origin entity (Subscription, Booking, etc.).
     */
    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the invoice's line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to filter by status.
     */
    public function scopeStatus(Builder $query, InvoiceStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get draft invoices.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::DRAFT);
    }

    /**
     * Scope to get sent/unpaid invoices.
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::OVERDUE, InvoiceStatus::PARTIALLY_PAID]);
    }

    /**
     * Scope to get paid invoices.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::OVERDUE)
            ->orWhere(function (Builder $q) {
                $q->where('status', InvoiceStatus::SENT)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now());
            });
    }

    /**
     * Scope to filter by billable entity.
     */
    public function scopeForBillable(Builder $query, Model $billable): Builder
    {
        return $query->where('billable_type', $billable->getMorphClass())
            ->where('billable_id', $billable->getKey());
    }

    // ========================================
    // ACCESSORS & HELPERS
    // ========================================

    /**
     * Get the formatted invoice number or draft placeholder.
     */
    public function getDisplayNumberAttribute(): string
    {
        return $this->number ?? "DRAFT-{$this->id}";
    }

    /**
     * Get the billable's name for display.
     */
    public function getBillableNameAttribute(): string
    {
        $billable = $this->billable;

        if (!$billable) {
            return 'Unknown';
        }

        // Try different common name attributes
        return $billable->name
            ?? $billable->user?->name
            ?? $billable->company_name
            ?? 'Customer #' . $this->billable_id;
    }

    /**
     * Get the billable's email for display.
     */
    public function getBillableEmailAttribute(): ?string
    {
        $billable = $this->billable;

        if (!$billable) {
            return null;
        }

        return $billable->email ?? $billable->user?->email;
    }

    /**
     * Check if the invoice is editable.
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if the invoice is finalized.
     */
    public function isFinalized(): bool
    {
        return $this->status->isFinalized();
    }

    /**
     * Check if the invoice can be paid.
     */
    public function canBePaid(): bool
    {
        return $this->status->canBePaid();
    }

    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === InvoiceStatus::OVERDUE) {
            return true;
        }

        return $this->due_date
            && $this->due_date->isPast()
            && $this->status->canBePaid();
    }

    /**
     * Get the amount due (total minus any partial payments).
     */
    public function getAmountDueAttribute(): float
    {
        // For now, return full total. Extend for partial payments if needed.
        if ($this->status === InvoiceStatus::PAID) {
            return 0.00;
        }

        return (float) $this->total;
    }

    /**
     * Calculate totals from line items.
     */
    public function calculateTotals(): void
    {
        $this->loadMissing('items');

        $subtotal = $this->items->sum(fn (InvoiceItem $item) => (float) $item->unit_price * $item->quantity);
        $itemDiscounts = $this->items->sum(fn (InvoiceItem $item) => (float) $item->discount_amount);

        $this->subtotal = $subtotal;
        $taxableAmount = $subtotal - $itemDiscounts - (float) $this->discount_amount;
        $this->tax_amount = round($taxableAmount * ((float) $this->tax_rate / 100), 2);
        $this->total = round($taxableAmount + (float) $this->tax_amount, 2);
    }

    /**
     * Get PDF storage path.
     */
    public function getPdfStoragePath(): string
    {
        $year = $this->issue_date?->year ?? now()->year;
        $number = $this->number ?? "draft-{$this->id}";

        return "invoices/{$year}/{$number}.pdf";
    }
}
