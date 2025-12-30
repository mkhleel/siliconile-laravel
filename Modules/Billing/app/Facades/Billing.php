<?php

namespace Modules\Billing\Facades;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Modules\Billing\Models\Order;
use Modules\Billing\Models\Transaction;
use Modules\Payment\Models\Payment;

/**
 * @method static Order createOrder(User $user, Model $product, array $options = [])
 * @method static Order createCompleteOrder(User $user, array $items, array $options = [])
 * @method static Order processPayment(Order $order, Payment $payment)
 * @method static Transaction recordTransaction(User $user, Model $payable, float $amount, string $status = 'completed', array $attributes = [])
 * @method static Collection getUserTransactions(User $user)
 * @method static Collection getUserOrders(User $user)
 * @method static Collection getOrdersByProductType(string $productType)
 * @method static Order processRefund(Order $order, float $amount = null, string $reason = null)
 * @method static float calculateTax($subtotal, $country = null, $state = null)
 *
 * @see \Modules\Billing\Services\BillingService
 */
class Billing extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'billing';
    }
}
