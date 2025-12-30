<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Enums\OrderStatus;
use Modules\Billing\Models\Order;
use Modules\Billing\Models\Transaction;
use Modules\Payment\Models\Payment;

/**
 * BillingService - Core business logic for order and transaction management.
 *
 * Handles:
 * - Order creation and management
 * - Transaction recording
 * - Refund processing
 * - Tax calculations
 */
class BillingService
{
    /**
     * Create an order for a single product
     */
    public function createOrder(User $user, $product, array $options = []): Order
    {
        try {
            return Order::createOrder($user, $product, $options);
        } catch (Exception $e) {
            Log::error('Error creating order: '.$e->getMessage(), [
                'user_id' => $user->id,
                'product' => get_class($product).':'.$product->id,
                'options' => $options,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Create a complete order with multiple items
     */
    public function createCompleteOrder(User $user, array $items, array $options = []): Order
    {
        try {
            return Order::createCompleteOrder($user, $items, $options);
        } catch (Exception $e) {
            Log::error('Error creating complete order: '.$e->getMessage(), [
                'user_id' => $user->id,
                'items' => count($items).' items',
                'options' => $options,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Process a payment for an order
     */
    public function processPayment(Order $order, Payment $payment): Order
    {
        try {
            // Handle payment completion logic
            $order->handlePaymentCompleted($payment);

            return $order;
        } catch (Exception $e) {
            Log::error('Error processing payment: '.$e->getMessage(), [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Record a transaction
     */
    public function recordTransaction(User $user, Model $payable, float $amount, string $status = 'completed', array $attributes = []): Transaction
    {
        try {
            $transaction = new Transaction;
            $transaction->user_id = $user->id;
            $transaction->payable_id = $payable->id;
            $transaction->payable_type = get_class($payable);
            $transaction->amount = $amount;
            $transaction->status = $status;
            $transaction->currency = $attributes['currency'] ?? config('billing.default_currency', 'USD');
            $transaction->reference = $attributes['reference'] ?? null;
            $transaction->gateway = $attributes['gateway'] ?? null;
            $transaction->meta = $attributes['meta'] ?? null;

            if ($status === Transaction::STATUS_COMPLETED) {
                $transaction->completed_at = now();
            }

            $transaction->save();

            return $transaction;
        } catch (Exception $e) {
            Log::error('Error recording transaction: '.$e->getMessage(), [
                'user_id' => $user->id,
                'payable' => get_class($payable).':'.$payable->id,
                'amount' => $amount,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Get all transactions for a user
     */
    public function getUserTransactions(User $user)
    {
        return Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all orders for a user
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>
     */
    public function getUserOrders(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all invoices for a user.
     *
     * TODO: Implement Invoice model when invoicing feature is added.
     *
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function getUserInvoices(User $user): \Illuminate\Support\Collection
    {
        // Invoice model not yet implemented - return empty collection
        // Future: return Invoice::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        return collect();
    }

    /**
     * Get orders by product type
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>
     */
    public function getOrdersByProductType(string $productType): \Illuminate\Database\Eloquent\Collection
    {
        return Order::where('orderable_type', $productType)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Process a refund for an order
     */
    public function processRefund(Order $order, ?float $amount = null, ?string $reason = null): Order
    {
        try {
            // If amount is null, refund the full order amount
            $refundAmount = $amount ?? $order->total;

            // Record the refund transaction
            $this->recordTransaction(
                $order->user,
                $order,
                -$refundAmount, // Negative amount for refund
                Transaction::STATUS_REFUNDED,
                [
                    'reference' => 'refund_'.$order->order_number,
                    'gateway' => $order->payment_gateway,
                    'meta' => [
                        'reason' => $reason,
                        'original_order_id' => $order->id,
                    ],
                ]
            );

            // Update order status
            $order->status = OrderStatus::REFUNDED;

            if ($reason) {
                $meta = $order->meta ?? [];
                $meta['refund_reason'] = $reason;
                $meta['refunded_at'] = now()->toDateTimeString();
                $meta['refund_amount'] = $refundAmount;
                $order->meta = $meta;
            }

            $order->save();

            return $order;
        } catch (Exception $e) {
            Log::error('Error processing refund: '.$e->getMessage(), [
                'order_id' => $order->id,
                'amount' => $amount,
                'reason' => $reason,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Calculate tax for an order based on amount and location
     */
    public function calculateTax($subtotal, $country = null, $state = null): float
    {
        // Simple implementation - would be expanded with tax rules by country/region
        if (! config('billing.tax_enabled', true)) {
            return 0;
        }

        $defaultRate = config('billing.default_tax_rate', 0);

        // Example tax calculation - in a real app, you would use a tax service or lookup tables
        // based on country and state
        $taxRate = match ($country) {
            'US' => match ($state) {
                'CA' => 0.095, // 9.5% for California
                'NY' => 0.085, // 8.5% for New York
                'TX' => 0.0625, // 6.25% for Texas
                default => $defaultRate,
            },
            'CA' => 0.13, // 13% for Canada
            'GB' => 0.20, // 20% for UK
            'DE' => 0.19, // 19% for Germany
            default => $defaultRate,
        };

        return round($subtotal * $taxRate, 2);
    }
}
