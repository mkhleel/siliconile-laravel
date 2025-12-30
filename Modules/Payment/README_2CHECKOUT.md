# 2Checkout Payment Gateway Integration

This document provides instructions on how to use the 2Checkout payment gateway integration.

## Configuration

Add the following environment variables to your `.env` file:

```env
# 2Checkout Payment Gateway Configuration
TWOCHECKOUT_ENABLED=true
TWOCHECKOUT_SANDBOX=true
TWOCHECKOUT_SELLER_ID=your_seller_id_here
TWOCHECKOUT_PRIVATE_KEY=your_private_key_here
TWOCHECKOUT_PUBLISHABLE_KEY=your_publishable_key_here
TWOCHECKOUT_BUY_LINK_SECRET_WORD=your_secret_word_here
TWOCHECKOUT_API_URL=https://api.2checkout.com/rest/6.0/
```

## Getting 2Checkout Credentials

1. Log into your 2Checkout account
2. Go to **Integrations > Webhooks & API**
3. Find your:
   - **Seller ID** (Merchant Code)
   - **Secret Key** (Private Key)
   - **Publishable Key** (for frontend integration)
   - **Secret Word** (for Buy Links)

## Usage

### 1. Processing Payments

```php
use Modules\Payment\Gateways\TwoCheckoutGateway;

$gateway = new TwoCheckoutGateway();

$paymentData = [
    'amount' => 100.00,
    'currency' => 'USD',
    'reference' => 'INV-12345',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'description' => 'Payment for services',
    'country' => 'US',
    'address' => [
        'address1' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'zip' => '10001',
        'phone' => '+1234567890',
    ],
    // For direct card processing
    'card_number' => '4111111111111111',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123',
    'cardholder_name' => 'John Doe',
    // OR for token-based processing
    'token' => 'ees_token_from_frontend',
];

$result = $gateway->processPayment($paymentData);

if ($result['success']) {
    echo "Payment successful: " . $result['transaction_id'];
} else {
    echo "Payment failed: " . $result['message'];
}
```

### 2. Generating Buy Links (Hosted Checkout)

```php
$buyLink = $gateway->generateBuyLink($paymentData);
// Redirect user to $buyLink for hosted checkout
return redirect($buyLink);
```

### 3. Handling Webhooks (IPN)

Create a webhook endpoint in your routes:

```php
// routes/web.php
Route::post('/webhooks/2checkout', function (\Illuminate\Http\Request $request) {
    $gateway = new TwoCheckoutGateway();
    $result = $gateway->handleWebhook($request);
    
    if ($result['success']) {
        // Update your payment record
        // Example: Payment::where('reference', $result['transaction_id'])->update(['status' => $result['status']]);
    }
    
    // Always return the IPN response token
    return response($result['ipn_response'] ?? 'OK');
});
```

### 4. Verifying Payment Status

```php
$result = $gateway->verifyPayment('payment_reference_number');

if ($result['success']) {
    echo "Payment verified: " . $result['status'];
} else {
    echo "Verification failed: " . $result['message'];
}
```

### 5. Processing Refunds

```php
$result = $gateway->refundPayment('payment_reference_number', 50.00, 'Partial refund');

if ($result['success']) {
    echo "Refund processed: " . $result['refund_id'];
} else {
    echo "Refund failed: " . $result['message'];
}
```

## Frontend Integration

Include the 2Checkout JavaScript SDK in your frontend:

```html
<script src="https://2pay-js.2checkout.com/v1/2pay.js"></script>
```

Then use it to tokenize credit card information:

```javascript
// Initialize 2Checkout
window.tco.setup({
    sellerId: 'YOUR_SELLER_ID',
    publishableKey: 'YOUR_PUBLISHABLE_KEY'
});

// Tokenize card information
window.tco.requestToken({
    cardNumber: '4111111111111111',
    cvv: '123',
    expMonth: '12',
    expYear: '2025',
    currency: 'USD'
}, function(data) {
    if (data.response === 'Success') {
        // Send the token to your backend
        processPaymentWithToken(data.token);
    } else {
        console.error('Tokenization failed:', data.errorMsg);
    }
});
```

## Filament Admin Panel

The integration includes a Filament resource for managing payments in the admin panel:

- View all payments with filtering and sorting
- Monitor payment statuses
- View detailed payment information
- Verify pending payments

Access the payment management at `/admin/payments` (assuming your admin panel is at `/admin`).

## Status Mapping

2Checkout statuses are mapped to internal statuses:

- `COMPLETE` → `completed`
- `AUTHRECEIVED` → `authorized`
- `PENDING` → `pending`
- `CANCELED` → `cancelled`
- `REFUND` → `refunded`
- `REVERSED` → `reversed`
- `FRAUD` → `fraud`

## Error Handling

All gateway methods return a standardized response format:

```php
[
    'success' => true|false,
    'message' => 'Human readable message',
    'transaction_id' => 'Gateway transaction ID',
    'data' => [...], // Raw gateway response
    'status' => 'internal_status',
    'redirect_url' => 'URL for 3DS or hosted checkout' // if applicable
]
```

## Security Notes

1. Never store credit card information in your database
2. Use tokenization for frontend card processing
3. Always validate webhook signatures
4. Use HTTPS for all payment-related endpoints
5. Keep your secret keys secure and never expose them in frontend code

## Testing

For testing, set `TWOCHECKOUT_SANDBOX=true` and use 2Checkout's test card numbers:

- **Visa**: `4111111111111111`
- **Mastercard**: `5431111111111111`
- **Amex**: `341111111111111`

Test transactions will not process real money.
