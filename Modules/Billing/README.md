# Billing Module

## Overview

The Billing module provides order and transaction management for purchasable items in your app (courses, products, subscriptions, etc.). It integrates with the Payment module to finalize payments and emit lifecycle events. Models include `Order`, `OrderItem`, and `Transaction`, plus a `Billable` concern you can add to any product model.

## Features

- Order creation for single and multiple items
- Transaction recording and querying
- Refund processing (full or partial)
- Basic tax calculation helper
- Events for order lifecycle (`OrderPaid`, `OrderCompleted`, `OrderCancelled`)
- Filament admin resource for managing orders

Note: Invoice generation is not implemented in the current codebase. Some web routes reference invoice actions for future/optional use.

## Installation

Run the module migrations:

```bash
php artisan migrate
```

Optionally publish and adjust configuration at `Modules/Billing/config/config.php`.

## Quick Start

### Create an order

```php
use Modules\Billing\Facades\Billing;
use App\Models\User;

$user = User::find(1);
$product = \Modules\Course\Models\Course::find(1); // any billable model

// Single-item order
$order = Billing::createOrder($user, $product, [
    'currency' => 'USD',
    'tax' => 5.00,
    'discount' => 10.00,
    'note' => 'Order created via API',
]);

// Multi-item order
$items = [
    ['product' => \Modules\Course\Models\Course::find(1), 'quantity' => 1, 'price' => 99.99],
    ['product' => \Modules\Shop\Models\Product::find(2), 'quantity' => 2],
];

$order = Billing::createCompleteOrder($user, $items, [
    'currency' => 'USD',
    'tax' => 15.00,
    'discount' => 20.00,
    'note' => 'Complete order with multiple items',
    'billing_address' => [...],
    'shipping_address' => [...],
]);
```

### Process a successful payment

```php
use Modules\Payment\Models\Payment;

// After your payment gateway confirms payment
$payment = Payment::find(1);

// Updates order status, records a transaction, emits events
$updatedOrder = Billing::processPayment($order, $payment);
```

### Refund an order

```php
// Full refund
$refunded = Billing::processRefund($order, null, 'Customer requested refund');

// Partial refund
$refunded = Billing::processRefund($order, 25.00, 'Partial refund for damaged item');
```

### Query orders and transactions

```php
$orders = Billing::getUserOrders($user);
$transactions = Billing::getUserTransactions($user);

// Filter by product type (morph class)
$courseOrders = Billing::getOrdersByProductType(\Modules\Course\Models\Course::class);
```

### Tax calculation helper

```php
$taxAmount = Billing::calculateTax(100.00, 'US', 'CA');
```

## Facade API

The `Modules\Billing\Facades\Billing` facade proxies to `BillingService` and exposes:

- `createOrder(User $user, Model $product, array $options = []): Order`
- `createCompleteOrder(User $user, array $items, array $options = []): Order`
- `processPayment(Order $order, Payment $payment): Order`
- `recordTransaction(User $user, Model $payable, float $amount, string $status = 'completed', array $attributes = []): Transaction`
- `getUserTransactions(User $user): Collection`
- `getUserOrders(User $user): Collection`
- `getOrdersByProductType(string $productType): Collection`
- `processRefund(Order $order, ?float $amount = null, ?string $reason = null): Order`
- `calculateTax($subtotal, $country = null, $state = null): float`

## Models and Concern

- `Modules\Billing\Models\Order`
  - Static: `createOrder`, `createCompleteOrder`
  - Instance: `addItem`, `calculateTotals`, `handlePaymentCompleted`, `cancel`, `isPaid`
  - Relations: `user`, `items`, `transactions`, `orderable`
- `Modules\Billing\Models\OrderItem`
  - Attributes: `name`, `price`, `quantity`, `product_id`, `product_type`
  - Accessor: `subtotal`
  - Relations: `order`, `product`
- `Modules\Billing\Models\Transaction`
  - Statuses: `pending`, `completed`, `failed`, `refunded`, `cancelled`
  - Scopes: `completed()`, `failed()`, `refunded()`
  - Helpers: `markCompleted()`, `markFailed($reason = null)`
- `Modules\Billing\Concerns\Billable`
  - Add to any purchasable model; provides `orders()`, `orderItems()`, `createOrder(User)`, `isPurchasedBy(User)`, etc.

## Routes

Web routes are prefixed with `billing.`:

- `GET /billing/invoices/{invoice}/pdf` → download invoice PDF
- `GET /billing/invoices/{invoice}/send` → send invoice via email
- `GET /billing/payment/success` → success landing
- `GET /billing/payment/cancel` → cancel landing

Note: The invoice routes assume an `Invoice` model and `generatePdf()` implementation not present in this module. You can provide these via your app or another module, or disable those routes if not needed.

API routes stub exists under `api/v1` and can be extended as needed.

## Configuration

Edit `Modules/Billing/config/config.php`:

- `default_currency` (env: `BILLING_DEFAULT_CURRENCY`)
- `default_tax_rate`, `tax_enabled`, `tax_included`
- `order_prefix`
- Company info: `company_name`, `company_address`, `company_phone`, `company_email`, `company_logo`, `company_vat`
- PDF options: `pdf_paper_size`, `pdf_orientation`
- Payment defaults: `default_payment_gateway`, `payment_success_redirect`, `payment_cancel_redirect`
- `receipt_template`

## Events

Listen for:

- `Modules\Billing\Events\OrderPaid`
- `Modules\Billing\Events\OrderCompleted`
- `Modules\Billing\Events\OrderCancelled`

## Filament Admin

The module ships a Filament resource for orders (`OrderResource`) with pages for listing, creating, viewing, and editing orders.

## Integration with Payment Module

1) The Payment module collects funds and creates a `Payment` record.
2) Call `Billing::processPayment($order, $payment)` to mark the order completed, record a transaction, and emit events.

Example controller callback:

```php
public function handlePaymentCallback(Request $request)
{
    $payment = $this->paymentService->processPaymentCallback($request);

    if ($payment->status === 'completed') {
        $order = Order::where('order_number', $payment->meta['order_number'] ?? null)->firstOrFail();
        Billing::processPayment($order, $payment);
        return redirect()->route('billing.payment.success');
    }

    return redirect()->route('billing.payment.cancel');
}
```

## Troubleshooting

- Ensure migrations have been run.
- Verify the Payment module is configured and payments supply `gateway`, `amount`, and a reference.
- If using invoice routes, implement an `Invoice` model with `generatePdf()` and related policies/notifications.
- Check Laravel logs for detailed error information.