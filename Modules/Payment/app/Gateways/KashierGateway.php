<?php

declare(strict_types=1);

namespace Modules\Payment\Gateways;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kashier.io Payment Gateway Integration
 *
 * Implements the Kashier Hosted Checkout (Payment Sessions) method.
 * Supports payment session creation, webhook handling, refunds, and order reconciliation.
 *
 * @see https://developers.kashier.io/payment/payment-sessions
 */
final class KashierGateway extends AbstractPaymentGateway
{
    /**
     * Kashier Merchant ID.
     */
    private string $merchantId;

    /**
     * Kashier Secret Key for API authentication.
     */
    private string $secretKey;

    /**
     * Kashier Payment API Key for hash generation and webhook signature verification.
     */
    private string $apiKey;

    /**
     * Base URL for Kashier API (test or live).
     */
    private string $baseUrl;

    /**
     * Whether to use test mode.
     */
    private bool $testMode;

    /**
     * Default currency for transactions.
     */
    private string $currency = 'EGP';

    /**
     * Display language for the payment UI.
     */
    private string $displayLang = 'en';

    /**
     * Allowed payment methods.
     */
    private string $allowedMethods = 'card,wallet';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(config('payment.gateways.kashier', []));

        if ($this->isEnabled()) {
            $this->initializeKashier();
        }
    }

    /**
     * Initialize Kashier with configuration.
     */
    private function initializeKashier(): void
    {
        $this->merchantId = $this->config['merchant_id'] ?? '';
        $this->secretKey = $this->config['secret_key'] ?? '';
        $this->apiKey = $this->config['api_key'] ?? '';
        $this->testMode = (bool) ($this->config['test_mode'] ?? true);
        $this->currency = $this->config['default_currency'] ?? 'EGP';
        $this->displayLang = $this->config['display_lang'] ?? 'en';
        $this->allowedMethods = $this->config['allowed_methods'] ?? 'card,wallet';

        // Set base URL based on mode
        $this->baseUrl = $this->testMode
            ? 'https://test-api.kashier.io'
            : 'https://api.kashier.io';
    }

    /**
     * Get the display name of the payment gateway.
     */
    public function getName(): string
    {
        return 'Kashier';
    }

    /**
     * Get the unique identifier of the payment gateway.
     */
    public function getIdentifier(): string
    {
        return 'kashier';
    }

    /**
     * Get the description of the payment gateway.
     */
    public function getDescription(): string
    {
        return 'Pay securely with credit card or mobile wallet via Kashier';
    }

    /**
     * Process the payment using Kashier Payment Sessions.
     *
     * @param  array<string, mixed>  $paymentData  Payment data including amount, currency, customer info
     * @return array<string, mixed> Result array with success status, redirect URL, etc.
     *
     * @see https://developers.kashier.io/payment/payment-sessions
     */
    public function processPayment(array $paymentData): array
    {
        try {
            $orderId = $paymentData['reference'] ?? uniqid('kashier_', true);
            $amount = number_format((float) $paymentData['amount'], 2, '.', '');
            $currency = $paymentData['currency'] ?? $this->currency;

            // Build payment session request payload
            $requestPayload = [
                'merchantId' => $this->merchantId,
                'amount' => $amount,
                'currency' => $currency,
                'order' => $orderId,
                'mode' => $this->testMode ? 'test' : 'live',
                'type' => 'one-time',
                'paymentType' => 'credit',
                'display' => $this->displayLang,
                'allowedMethods' => $this->allowedMethods,
                'expireAt' => now()->addHours(24)->toIso8601String(),
                'maxFailureAttempts' => 3,
            ];

            // Add merchant redirect URL
            $redirectUrl = $paymentData['return_url'] ?? route('payment.verify', ['gateway' => 'kashier']);
            $requestPayload['merchantRedirect'] = urlencode($redirectUrl);

            // Add server webhook URL for server-to-server notifications
            $webhookUrl = route('payment.webhook.handler', ['gateway' => 'kashier']);
            $requestPayload['serverWebhook'] = $webhookUrl;

            // Add customer information if available
            if (isset($paymentData['customer_email']) || isset($paymentData['customer_name'])) {
                $customer = [];
                if (isset($paymentData['customer_email'])) {
                    $customer['email'] = $paymentData['customer_email'];
                }
                if (isset($paymentData['customer_name'])) {
                    $customer['name'] = $paymentData['customer_name'];
                }
                // Generate a reference for the customer
                $customer['reference'] = $paymentData['customer_id'] ?? md5($paymentData['customer_email'] ?? $orderId);
                $requestPayload['customer'] = $customer;
            }

            // Add metadata
            $metadata = $paymentData['metadata'] ?? [];
            $metadata['payment_id'] = $paymentData['payment_id'] ?? null;
            $metadata['reference'] = $orderId;
            $requestPayload['metaData'] = $metadata;

            // Add description if provided
            if (isset($paymentData['description'])) {
                $requestPayload['description'] = substr($paymentData['description'], 0, 120);
            }

            // Add notes if provided
            if (isset($paymentData['notes'])) {
                $requestPayload['notes'] = $paymentData['notes'];
            }

            // Brand color customization
            if (isset($this->config['brand_color'])) {
                $requestPayload['brandColor'] = $this->config['brand_color'];
            }

            // Iframe background color
            if (isset($this->config['iframe_bg_color'])) {
                $requestPayload['iframeBackgroundColor'] = $this->config['iframe_bg_color'];
            }

            Log::info('Kashier Payment Session Request', [
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->secretKey,
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v3/payment/sessions", $requestPayload);

            $responseData = $response->json();

            if (! $response->successful()) {
                Log::error('Kashier Payment Session Failed', [
                    'response' => $responseData,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Failed to create payment session',
                    'status' => 'failed',
                ];
            }

            // Check for session URL in response
            $sessionId = $responseData['_id'] ?? $responseData['sessionId'] ?? null;
            $sessionUrl = $responseData['sessionUrl'] ?? null;

            // If sessionUrl is not directly provided, construct it
            if (! $sessionUrl && $sessionId) {
                $mode = $this->testMode ? 'test' : 'live';
                $sessionUrl = "https://payments.kashier.io/session/{$sessionId}?mode={$mode}";
            }

            if (! $sessionUrl) {
                Log::error('Kashier: No session URL in response', [
                    'response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to get payment session URL',
                    'status' => 'failed',
                ];
            }

            Log::info('Kashier Payment Session Created', [
                'session_id' => $sessionId,
                'order_id' => $orderId,
            ]);

            return [
                'success' => true,
                'message' => 'Redirecting to payment gateway',
                'transaction_id' => $sessionId,
                'session_id' => $sessionId,
                'data' => $responseData,
                'status' => 'pending',
                'redirect_url' => $sessionUrl,
            ];

        } catch (Exception $e) {
            Log::error('Kashier Payment Error: '.$e->getMessage(), [
                'payment_data' => array_diff_key($paymentData, array_flip(['customer_email'])),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed',
                'status' => 'failed',
            ];
        }
    }

    /**
     * Handle webhook notifications from Kashier.
     *
     * Kashier sends webhooks for payment events (pay, refund, authorize, void, capture).
     * The webhook payload includes a signature that must be verified using the API key.
     *
     * @see https://developers.kashier.io/webhooks/setup
     */
    public function handleWebhook(Request $request): array
    {
        try {
            $payload = $request->all();
            $event = $payload['event'] ?? null;
            $data = $payload['data'] ?? [];

            Log::info('Kashier Webhook Received', [
                'event' => $event,
                'merchant_order_id' => $data['merchantOrderId'] ?? null,
                'kashier_order_id' => $data['kashierOrderId'] ?? null,
            ]);

            // Verify webhook signature
            $receivedSignature = $request->header('x-kashier-signature');
            if ($receivedSignature && ! $this->verifyWebhookSignature($data, $receivedSignature)) {
                Log::warning('Kashier Webhook: Invalid signature', [
                    'received_signature' => $receivedSignature,
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid webhook signature',
                    'status' => 'failed',
                ];
            }

            // Extract transaction details
            $merchantOrderId = $data['merchantOrderId'] ?? null;
            $kashierOrderId = $data['kashierOrderId'] ?? null;
            $transactionId = $data['transactionId'] ?? null;
            $status = $data['status'] ?? null;
            $amount = $data['amount'] ?? null;
            $currency = $data['currency'] ?? null;
            $responseCode = $data['transactionResponseCode'] ?? null;

            // Handle different event types
            $result = match ($event) {
                'pay' => $this->handlePaymentEvent($data, $status, $responseCode),
                'refund' => $this->handleRefundEvent($data, $status),
                'authorize' => $this->handleAuthorizeEvent($data, $status),
                'capture' => $this->handleCaptureEvent($data, $status),
                'void' => $this->handleVoidEvent($data, $status),
                default => [
                    'success' => false,
                    'message' => "Unknown event type: {$event}",
                    'status' => 'unknown',
                ],
            };

            // Add common data to result
            $result['transaction_id'] = $transactionId ?? $kashierOrderId;
            $result['order_id'] = $merchantOrderId;
            $result['kashier_order_id'] = $kashierOrderId;
            $result['amount'] = $amount;
            $result['currency'] = $currency;
            $result['data'] = $data;
            $result['event'] = $event;

            return $result;

        } catch (Exception $e) {
            Log::error('Kashier Webhook Error: '.$e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing failed',
                'status' => 'failed',
            ];
        }
    }

    /**
     * Handle payment event from webhook.
     *
     * @param  array<string, mixed>  $data  Webhook data
     * @param  string|null  $status  Payment status
     * @param  string|null  $responseCode  Response code
     * @return array<string, mixed>
     */
    private function handlePaymentEvent(array $data, ?string $status, ?string $responseCode): array
    {
        // SUCCESS status and response code 00 indicates successful payment
        if ($status === 'SUCCESS' && $responseCode === '00') {
            return [
                'success' => true,
                'message' => 'Payment completed successfully',
                'status' => 'completed',
            ];
        }

        // Map Kashier status to internal status
        $mappedStatus = $this->mapKashierStatus($status);

        return [
            'success' => $mappedStatus === 'completed',
            'message' => $data['transactionResponseMessage']['en'] ?? 'Payment status: '.($status ?? 'unknown'),
            'status' => $mappedStatus,
        ];
    }

    /**
     * Handle refund event from webhook.
     *
     * @param  array<string, mixed>  $data  Webhook data
     * @param  string|null  $status  Refund status
     * @return array<string, mixed>
     */
    private function handleRefundEvent(array $data, ?string $status): array
    {
        if ($status === 'SUCCESS') {
            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'status' => 'refunded',
            ];
        }

        return [
            'success' => false,
            'message' => 'Refund status: '.($status ?? 'unknown'),
            'status' => $status === 'PENDING' ? 'pending' : 'failed',
        ];
    }

    /**
     * Handle authorize event from webhook.
     *
     * @param  array<string, mixed>  $data  Webhook data
     * @param  string|null  $status  Authorization status
     * @return array<string, mixed>
     */
    private function handleAuthorizeEvent(array $data, ?string $status): array
    {
        if ($status === 'SUCCESS') {
            return [
                'success' => true,
                'message' => 'Authorization successful',
                'status' => 'authorized',
            ];
        }

        return [
            'success' => false,
            'message' => 'Authorization status: '.($status ?? 'unknown'),
            'status' => $this->mapKashierStatus($status),
        ];
    }

    /**
     * Handle capture event from webhook.
     *
     * @param  array<string, mixed>  $data  Webhook data
     * @param  string|null  $status  Capture status
     * @return array<string, mixed>
     */
    private function handleCaptureEvent(array $data, ?string $status): array
    {
        if ($status === 'SUCCESS') {
            return [
                'success' => true,
                'message' => 'Capture successful',
                'status' => 'completed',
            ];
        }

        return [
            'success' => false,
            'message' => 'Capture status: '.($status ?? 'unknown'),
            'status' => $this->mapKashierStatus($status),
        ];
    }

    /**
     * Handle void event from webhook.
     *
     * @param  array<string, mixed>  $data  Webhook data
     * @param  string|null  $status  Void status
     * @return array<string, mixed>
     */
    private function handleVoidEvent(array $data, ?string $status): array
    {
        if ($status === 'SUCCESS') {
            return [
                'success' => true,
                'message' => 'Payment voided successfully',
                'status' => 'cancelled',
            ];
        }

        return [
            'success' => false,
            'message' => 'Void status: '.($status ?? 'unknown'),
            'status' => $this->mapKashierStatus($status),
        ];
    }

    /**
     * Verify the webhook signature from Kashier.
     *
     * The signature is generated by:
     * 1. Sorting the signatureKeys array alphabetically
     * 2. Creating a query string from those keys and their URL-encoded values
     * 3. Hashing with HMAC SHA256 using the API key
     *
     * @see https://developers.kashier.io/webhooks/setup#signature
     */
    private function verifyWebhookSignature(array $data, string $receivedSignature): bool
    {
        try {
            // Get the signature keys from the data
            $signatureKeys = $data['signatureKeys'] ?? [];

            if (empty($signatureKeys)) {
                // If no signature keys provided, skip verification
                Log::warning('Kashier Webhook: No signature keys in payload');

                return true;
            }

            // Sort the signature keys alphabetically
            sort($signatureKeys);

            // Build the signature payload from the specified keys
            $signaturePayload = [];
            foreach ($signatureKeys as $key) {
                if (isset($data[$key])) {
                    $value = $data[$key];
                    // URL encode only the value, not the key
                    $signaturePayload[] = $key.'='.urlencode((string) $value);
                }
            }

            $signatureString = implode('&', $signaturePayload);

            // Generate HMAC SHA256 signature using the API key
            $calculatedSignature = hash_hmac('sha256', $signatureString, $this->apiKey);

            $isValid = hash_equals($calculatedSignature, $receivedSignature);

            if (! $isValid) {
                Log::debug('Kashier Signature Verification Failed', [
                    'signature_string' => $signatureString,
                    'calculated' => $calculatedSignature,
                    'received' => $receivedSignature,
                ]);
            }

            return $isValid;

        } catch (Exception $e) {
            Log::error('Kashier Signature Verification Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Verify the payment status with Kashier.
     *
     * Uses the Get Payment Session API to retrieve the current status.
     *
     * @see https://developers.kashier.io/payment/payment-sessions#get-payment-session
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->secretKey,
            ])->get("{$this->baseUrl}/v3/payment/sessions/{$paymentId}/payment");

            $responseData = $response->json();

            if (! $response->successful()) {
                Log::error('Kashier Payment Verification Failed', [
                    'response' => $responseData,
                    'payment_id' => $paymentId,
                ]);

                return [
                    'success' => false,
                    'message' => 'Payment verification failed',
                    'transaction_id' => $paymentId,
                    'status' => 'failed',
                ];
            }

            $data = $responseData['data'] ?? $responseData;
            $status = $data['status'] ?? null;
            $mappedStatus = $this->mapKashierStatus($status);

            Log::info('Kashier Payment Verification', [
                'payment_id' => $paymentId,
                'status' => $status,
                'mapped_status' => $mappedStatus,
            ]);

            return [
                'success' => $mappedStatus === 'completed',
                'message' => $responseData['message'] ?? 'Payment verification completed',
                'transaction_id' => $data['sessionId'] ?? $paymentId,
                'order_id' => $data['merchantOrderId'] ?? null,
                'data' => $data,
                'status' => $mappedStatus,
            ];

        } catch (Exception $e) {
            Log::error('Kashier Verification Error: '.$e->getMessage(), [
                'payment_id' => $paymentId,
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed',
                'transaction_id' => $paymentId,
                'status' => 'failed',
            ];
        }
    }

    /**
     * Retrieve order details for reconciliation.
     *
     * @see https://developers.kashier.io/payment/order-reconciliation
     *
     * @param  string  $merchantOrderId  The merchant's order ID
     * @return array<string, mixed>
     */
    public function getOrderDetails(string $merchantOrderId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->secretKey,
            ])->get("{$this->baseUrl}/v3/payment/orders", [
                'search' => $merchantOrderId,
            ]);

            $responseData = $response->json();

            if (! $response->successful() || $responseData['status'] !== 'SUCCESS') {
                Log::error('Kashier Order Details Failed', [
                    'response' => $responseData,
                    'merchant_order_id' => $merchantOrderId,
                ]);

                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Failed to retrieve order details',
                ];
            }

            return [
                'success' => true,
                'message' => 'Order details retrieved successfully',
                'data' => $responseData['data'] ?? [],
                'pagination' => $responseData['pagination'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('Kashier Get Order Details Error: '.$e->getMessage(), [
                'merchant_order_id' => $merchantOrderId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve order details',
            ];
        }
    }

    /**
     * Get transaction details.
     *
     * @see https://developers.kashier.io/payment/transactions
     *
     * @param  string  $transactionId  The Kashier transaction ID
     * @return array<string, mixed>
     */
    public function getTransactionDetails(string $transactionId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->secretKey,
            ])->get("{$this->baseUrl}/v2/aggregator/transactions/{$transactionId}");

            $responseData = $response->json();

            if (! $response->successful()) {
                Log::error('Kashier Transaction Details Failed', [
                    'response' => $responseData,
                    'transaction_id' => $transactionId,
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to retrieve transaction details',
                ];
            }

            return [
                'success' => true,
                'message' => 'Transaction details retrieved successfully',
                'data' => $responseData['body'] ?? $responseData,
            ];

        } catch (Exception $e) {
            Log::error('Kashier Get Transaction Details Error: '.$e->getMessage(), [
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve transaction details',
            ];
        }
    }

    /**
     * Issue a refund for a payment.
     *
     * @see https://developers.kashier.io/payment/refund
     *
     * @param  string  $orderId  The Kashier Order ID
     * @param  float|null  $amount  Amount to refund (null for full refund)
     * @param  string|null  $reason  Reason for refund
     * @return array<string, mixed>
     */
    public function refundPayment(string $orderId, ?float $amount = null, ?string $reason = null): array
    {
        try {
            // Use the correct refund endpoint
            $refundBaseUrl = $this->testMode
                ? 'https://test-fep.kashier.io'
                : 'https://fep.kashier.io';

            $requestPayload = [
                'apiOperation' => 'REFUND',
                'reason' => $reason ?? 'Customer requested refund',
                'transaction' => [],
            ];

            if ($amount !== null) {
                $requestPayload['transaction']['amount'] = $amount;
            }

            $response = Http::withHeaders([
                'Authorization' => $this->secretKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->put("{$refundBaseUrl}/v3/orders/{$orderId}", $requestPayload);

            $responseData = $response->json();

            if (! $response->successful()) {
                Log::error('Kashier Refund Failed', [
                    'response' => $responseData,
                    'order_id' => $orderId,
                ]);

                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Refund failed',
                    'transaction_id' => $orderId,
                    'status' => 'failed',
                ];
            }

            $refundStatus = $responseData['response']['status'] ?? null;

            Log::info('Kashier Refund Processed', [
                'order_id' => $orderId,
                'amount' => $amount,
                'status' => $refundStatus,
            ]);

            return [
                'success' => $refundStatus === 'SUCCESS',
                'message' => $refundStatus === 'SUCCESS' ? 'Refund processed successfully' : 'Refund pending',
                'transaction_id' => $orderId,
                'refund_id' => $responseData['response']['transactionId'] ?? null,
                'data' => $responseData['response'] ?? $responseData,
                'status' => $refundStatus === 'SUCCESS' ? 'refunded' : 'pending',
            ];

        } catch (Exception $e) {
            Log::error('Kashier Refund Error: '.$e->getMessage(), [
                'order_id' => $orderId,
                'amount' => $amount,
            ]);

            return [
                'success' => false,
                'message' => 'Refund failed',
                'transaction_id' => $orderId,
                'status' => 'failed',
            ];
        }
    }

    /**
     * Register/update webhook URL in Kashier system.
     *
     * @see https://developers.kashier.io/webhooks/endpoints
     *
     * @param  string  $webhookUrl  The webhook URL to register
     * @return array<string, mixed>
     */
    public function registerWebhookUrl(string $webhookUrl): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->secretKey,
                'Accept' => 'application/json',
            ])->put("{$this->baseUrl}/merchant", [
                'action' => 'webhook',
                'operation' => 'updatemerchantuser',
            ], [
                'MID' => $this->merchantId,
                'webhookUrl' => $webhookUrl,
            ]);

            $responseData = $response->json();

            if (! $response->successful()) {
                Log::error('Kashier Webhook Registration Failed', [
                    'response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to register webhook URL',
                ];
            }

            return [
                'success' => true,
                'message' => 'Webhook URL registered successfully',
                'data' => $responseData,
            ];

        } catch (Exception $e) {
            Log::error('Kashier Webhook Registration Error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to register webhook URL',
            ];
        }
    }

    /**
     * Map Kashier status to internal payment status.
     */
    private function mapKashierStatus(?string $status): string
    {
        return match ($status) {
            'SUCCESS', 'CAPTURED', 'PAID' => 'completed',
            'PENDING', 'CREATED', 'OPENED' => 'pending',
            'FAILED', 'DECLINED', 'REJECTED' => 'failed',
            'CANCELLED', 'VOIDED' => 'cancelled',
            'REFUNDED' => 'refunded',
            'AUTHORIZED' => 'authorized',
            default => 'unknown',
        };
    }

    /**
     * Get supported countries for this gateway.
     *
     * @return array<string>
     */
    public function getSupportedCountries(): array
    {
        // Kashier primarily operates in Egypt
        return $this->config['countries'] ?? ['EG'];
    }

    /**
     * Get test card numbers for testing.
     *
     * @see https://developers.kashier.io/payment/testing
     *
     * @return array<string, array<string, string>>
     */
    public static function getTestCards(): array
    {
        return [
            'mastercard_success' => [
                'number' => '5111111111111118',
                'holder' => 'Michel Doe',
                'expiry' => '06/25', // APPROVED
                'cvv' => '100', // MATCH
            ],
            'mastercard_success_2' => [
                'number' => '5123450000002346',
                'holder' => 'John Doe',
                'expiry' => '06/25',
                'cvv' => '100',
            ],
            'mastercard_3ds' => [
                'number' => '5123450000000008',
                'holder' => 'John Doe',
                'expiry' => '06/25',
                'cvv' => '100',
                'note' => '3D-Secure Enrolled',
            ],
            'visa_success' => [
                'number' => '4508750015741019',
                'holder' => 'John Doe',
                'expiry' => '06/25',
                'cvv' => '100',
            ],
            'visa_3ds' => [
                'number' => '4012000033330026',
                'holder' => 'John Doe',
                'expiry' => '06/25',
                'cvv' => '100',
                'note' => '3D-Secure Enrolled',
            ],
            'declined' => [
                'number' => '5111111111111118',
                'holder' => 'John Doe',
                'expiry' => '05/25', // DECLINED
                'cvv' => '100',
            ],
            'expired_card' => [
                'number' => '5111111111111118',
                'holder' => 'John Doe',
                'expiry' => '04/27', // EXPIRED_CARD
                'cvv' => '100',
            ],
        ];
    }

    /**
     * Get test mobile wallet number.
     *
     * @return array<string, string>
     */
    public static function getTestWallet(): array
    {
        return [
            'vodafone' => '01001001001',
        ];
    }
}
