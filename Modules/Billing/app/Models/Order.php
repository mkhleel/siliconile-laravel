<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Billing\Enums\OrderStatus;
use Modules\Billing\Events\OrderCancelled;
use Modules\Billing\Events\OrderCompleted;
use Modules\Billing\Events\OrderPaid;
use Modules\Billing\Observers\OrderObserver;
use Modules\Payment\Models\Payment;
use Modules\Payment\Traits\HasPayments;

/**
 * Order Model - Represents a customer purchase order.
 *
 * Trace: SRS-FR-BILLING-001 (Order Management)
 */
#[ObservedBy([OrderObserver::class])]
class Order extends Model
{
    use HasPayments;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'orderable_id',
        'orderable_type',
        'user_id',
        'order_number',
        'tracking_number',
        'currency',
        'subtotal',
        'discount_total',
        'tax',
        'total',
        'note',
        'payment_gateway',
        'status',
        'paid_at',
        'billing_address',
        'shipping_address',
        'meta',
    ];

    /**
     * Custom observable events for order lifecycle.
     *
     * @var array<string>
     */
    protected $observables = [
        'confirmed',
        'failed',
        'cancelled',
        'refunded',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'meta' => 'array',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the orderable entity (course, module, product, subscription).
     */
    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relationship to the customer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order's line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the order's status history.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the order's transactions.
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payable');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to get active/pending orders.
     *
     * @param Builder<Order> $query
     * @return Builder<Order>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->with('user')
            ->where('status', OrderStatus::PENDING->value);
    }

    /**
     * Scope to get completed orders.
     *
     * @param Builder<Order> $query
     * @return Builder<Order>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::COMPLETED->value);
    }

    // ========================================
    // STATUS MANAGEMENT
    // ========================================

    /**
     * Set the order status and trigger appropriate events.
     */
    public function setStatus(OrderStatus|string $status, ?string $description = null): void
    {
        $oldStatus = $this->status;

        // Update the status
        if ($status instanceof OrderStatus) {
            $this->status = $status;
        } else {
            $this->status = match ($status) {
                'pending' => OrderStatus::PENDING,
                'processing' => OrderStatus::PROCESSING,
                'shipped' => OrderStatus::SHIPPED,
                'out_for_delivery' => OrderStatus::OUT_FOR_DELIVERY,
                'delivered' => OrderStatus::DELIVERED,
                'completed' => OrderStatus::COMPLETED,
                'cancelled' => OrderStatus::CANCELLED,
                'refunded' => OrderStatus::REFUNDED,
                default => $this->status,
            };
        }

        if ($oldStatus !== $this->status) {
            $this->save();

            // Log status change to history
            $this->statusHistories()->create([
                'status' => $this->status,
                'description' => $description ?? $this->status->getDescription(),
            ]);
        }

        // Fire model events based on the status
        $statusValue = $status instanceof OrderStatus ? $status : $this->status;
        match ($statusValue) {
            OrderStatus::COMPLETED => $this->fireModelEvent('confirmed'),
            OrderStatus::CANCELLED => $this->fireModelEvent('cancelled'),
            OrderStatus::REFUNDED => $this->fireModelEvent('refunded'),
            default => null,
        };
    }

    // ========================================
    // ORDER OPERATIONS
    // ========================================

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(uniqid());
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Calculate totals for the order.
     */
    public function calculateTotals(): self
    {
        // Calculate subtotal from items
        $subtotal = $this->items->sum(fn ($item) => $item->price * $item->quantity);

        $this->subtotal = $subtotal;
        $this->total = $subtotal - ($this->discount_total ?? 0) + ($this->tax ?? 0);
        $this->save();

        return $this;
    }

    /**
     * Calculate the total weight of all products in the order.
     * Returns weight in kg for shipping calculation.
     */
    public function calculateTotalWeight(): float
    {
        return $this->items->sum(function ($item) {
            $product = $item->product;

            if ($product && method_exists($product, 'getShippingWeight')) {
                return $product->getShippingWeight() * $item->quantity;
            }

            return 0;
        });
    }

    /**
     * Create an order for a single item.
     *
     * @param array{currency?: string, discount?: float, tax?: float, note?: string} $options
     */
    public static function createOrder(User $user, Model $item, array $options = []): self
    {
        $price = $item->price ?? 0;
        $order = self::create([
            'user_id' => $user->id,
            'orderable_type' => $item::class,
            'orderable_id' => $item->id,
            'order_number' => self::generateOrderNumber(),
            'currency' => $options['currency'] ?? config('billing.default_currency', 'USD'),
            'subtotal' => $price,
            'discount_total' => $options['discount'] ?? 0,
            'tax' => $options['tax'] ?? 0,
            'total' => $price - ($options['discount'] ?? 0) + ($options['tax'] ?? 0),
            'status' => OrderStatus::PENDING,
            'note' => $options['note'] ?? 'Order created for ' . ($item->title ?? $item->name ?? 'product'),
        ]);

        // Create an order item
        $order->items()->create([
            'name' => $item->title ?? $item->name ?? 'Product',
            'price' => $price,
            'quantity' => 1,
            'product_id' => $item->id,
            'product_type' => $item::class,
        ]);

        return $order;
    }

    /**
     * Create a complete order with multiple items.
     *
     * @param array<array{product: Model, quantity?: int, price?: float}> $items
     * @param array{currency?: string, discount?: float, tax?: float, note?: string, billing_address?: array<string, mixed>, shipping_address?: array<string, mixed>} $options
     */
    public static function createCompleteOrder(User $user, array $items, array $options = []): self
    {
        $order = self::create([
            'user_id' => $user->id,
            'order_number' => self::generateOrderNumber(),
            'currency' => $options['currency'] ?? config('billing.default_currency', 'USD'),
            'subtotal' => 0, // Will be calculated from items
            'discount_total' => $options['discount'] ?? 0,
            'tax' => $options['tax'] ?? 0,
            'total' => 0, // Will be calculated from items
            'status' => OrderStatus::PENDING,
            'note' => $options['note'] ?? null,
            'billing_address' => $options['billing_address'] ?? null,
            'shipping_address' => $options['shipping_address'] ?? null,
        ]);

        // Create order items
        foreach ($items as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'] ?? 1;
            $price = $item['price'] ?? $product->price ?? 0;

            $order->items()->create([
                'name' => $product->title ?? $product->name ?? 'Product',
                'price' => $price,
                'quantity' => $quantity,
                'product_id' => $product->id,
                'product_type' => $product::class,
            ]);
        }

        // Calculate the totals based on items
        $order->calculateTotals();

        return $order;
    }

    /**
     * Add a new item to the order.
     */
    public function addItem(Model $product, int $quantity = 1, ?float $price = null): self
    {
        $price = $price ?? $product->price ?? 0;

        $this->items()->create([
            'name' => $product->title ?? $product->name ?? 'Product',
            'price' => $price,
            'quantity' => $quantity,
            'product_id' => $product->id,
            'product_type' => $product::class,
        ]);

        $this->calculateTotals();

        return $this;
    }

    /**
     * Handle payment completion for an order.
     */
    public function handlePaymentCompleted(Payment $payment): void
    {
        // Update order status
        $this->status = OrderStatus::COMPLETED;
        $this->paid_at = now();
        $this->payment_gateway = $payment->gateway ?? $payment->provider ?? null;
        $this->save();

        // Process inventory for products if needed
        $this->processInventory();

        // Create transaction record
        $this->transactions()->create([
            'user_id' => $this->user_id,
            'amount' => $payment->amount,
            'reference' => $payment->reference ?? $payment->id,
            'gateway' => $payment->gateway ?? $payment->provider ?? 'unknown',
            'status' => Transaction::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Trigger order completion process
        event(new OrderPaid($this));
        event(new OrderCompleted($this));
    }

    /**
     * Process inventory for physical products.
     */
    protected function processInventory(): void
    {
        // Decrease inventory for ordered items that are physical products
        foreach ($this->items as $item) {
            $product = $item->product;

            // Check if product has a decreaseStock method
            if ($product && method_exists($product, 'decreaseStock')) {
                $product->decreaseStock($item->quantity);
            }
        }
    }

    /**
     * Cancel an order.
     */
    public function cancel(?string $reason = null): self
    {
        $this->status = OrderStatus::CANCELLED;

        if ($reason) {
            $meta = $this->meta ?? [];
            $meta['cancellation_reason'] = $reason;
            $meta['cancelled_at'] = now()->toDateTimeString();
            $this->meta = $meta;
        }

        $this->save();

        event(new OrderCancelled($this));

        return $this;
    }

    /**
     * Check if the order has been paid.
     */
    public function isPaid(): bool
    {
        return $this->paid_at !== null || $this->status === OrderStatus::COMPLETED;
    }
}
