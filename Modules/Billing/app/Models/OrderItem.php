<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * OrderItem Model - Represents a line item in an order.
 *
 * Trace: SRS-FR-BILLING-002 (Order Items)
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'name',
        'price',
        'quantity',
        'product_id',
        'product_type',
        'options',
        'meta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'options' => 'array',
            'meta' => 'array',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the order this item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the actual product this item represents.
     */
    public function product(): MorphTo
    {
        return $this->morphTo('product');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get the subtotal for this item (price × quantity).
     */
    public function getSubtotalAttribute(): float
    {
        return (float) ($this->price * $this->quantity);
    }

    // ========================================
    // OPERATIONS
    // ========================================

    /**
     * Update the quantity of this item.
     */
    public function updateQuantity(int $quantity): self
    {
        // Don't allow negative quantities
        if ($quantity < 0) {
            $quantity = 0;
        }

        $this->quantity = $quantity;
        $this->save();

        // Recalculate the order totals
        $this->order->calculateTotals();

        return $this;
    }
}
