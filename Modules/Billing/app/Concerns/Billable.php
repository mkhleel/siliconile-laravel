<?php

namespace Modules\Billing\Concerns;

use App\Models\User;
use Modules\Billing\Facades\Billing;
use Modules\Billing\Models\Order;
use Modules\Billing\Models\OrderItem;

/**
 * Trait for product models that can be purchased
 * Apply this trait to any model that can be added to orders (courses, products, etc.)
 */
trait Billable
{
    /**
     * Get all orders for this product
     */
    public function orders()
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    /**
     * Get all order items containing this product
     */
    public function orderItems()
    {
        return $this->morphMany(OrderItem::class, 'product');
    }

    /**
     * Create a new order for this product
     */
    public function createOrder(User $user, array $data = []): Order
    {
        return Billing::createOrder($user, $this, $data);
    }

    /**
     * Check if a user has purchased this product
     */
    public function isPurchasedBy(User $user): bool
    {
        return $this->orders()
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'processing'])
            ->exists();
    }

    /**
     * Get all users who have purchased this product
     */
    public function purchasedBy()
    {
        return User::whereHas('orders', function ($query) {
            $query->where('orderable_type', get_class($this))
                ->where('orderable_id', $this->id)
                ->whereIn('status', ['completed', 'processing']);
        });
    }

    /**
     * Get the count of successful orders for this product
     */
    public function getPurchaseCountAttribute(): int
    {
        return $this->orders()
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get the formatted price with currency symbol
     */
    public function getFormattedPriceAttribute(): string
    {
        return formatCurrency($this->price, 2);
    }
}
