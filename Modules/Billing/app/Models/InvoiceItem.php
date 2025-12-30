<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * InvoiceItem Model - Represents a line item on an invoice.
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $description
 * @property int $quantity
 * @property string $unit_price
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total
 * @property string|null $origin_type
 * @property int|null $origin_id
 * @property array|null $metadata
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Invoice $invoice
 * @property-read Model|null $origin
 */
class InvoiceItem extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'invoice_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'total',
        'origin_type',
        'origin_id',
        'metadata',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'metadata' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $item) {
            // Auto-calculate line total
            $subtotal = (float) $item->unit_price * $item->quantity;
            $item->total = round($subtotal - (float) $item->discount_amount, 2);
        });

        static::saved(function (self $item) {
            // Recalculate invoice totals when item is saved
            $item->invoice?->calculateTotals();
            $item->invoice?->save();
        });

        static::deleted(function (self $item) {
            // Recalculate invoice totals when item is deleted
            $item->invoice?->calculateTotals();
            $item->invoice?->save();
        });
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the invoice this item belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the origin entity (Booking, Product, etc.).
     */
    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    // ========================================
    // ACCESSORS & HELPERS
    // ========================================

    /**
     * Get the line subtotal (before discount).
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }

    /**
     * Get formatted unit price.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format((float) $this->unit_price, 2) . ' ' . ($this->invoice?->currency ?? 'SAR');
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 2) . ' ' . ($this->invoice?->currency ?? 'SAR');
    }
}
