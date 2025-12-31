<?php

declare(strict_types=1);

namespace Modules\Payment\Gateways;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\CurrencyService;

final class PaytabsGateway extends AbstractPaymentGateway
{
    private string $profileId;

    private string $serverKey;

    private string $baseUrl;

    private string $checkoutLang;

    private string $currency = 'EGP';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(config('payment.gateways.paytabs'));

        if ($this->isEnabled()) {
            $this->initializePaytabs();
        }
    }

    /**
     * Initialize Paytabs with configuration
     */
    private function initializePaytabs(): void
    {
        $this->profileId = $this->config['profile_id'];
        $this->serverKey = $this->config['server_key'];
        $this->baseUrl = $this->config['base_url'];
        $this->checkoutLang = $this->config['checkout_lang'] ?? 'en';
        $this->currency = $this->config['default_currency'] ?? 'EGP';

    }

    /**
     * Get the display name of the payment gateway
     */
    public function getName(): string
    {
        return 'PayTabs';
    }

    /**
     * Get the unique identifier of the payment gateway
     */
    public function getIdentifier(): string
    {
        return 'paytabs';
    }

    /**
     * Get the description of the payment gateway
     */
    public function getDescription(): string
    {
        return 'Pay securely with your credit card via PayTabs';
    }

    /**
     * Process the payment
     */
    public function processPayment(array $paymentData): array
    {
        try {
            $cartId = $paymentData['reference'] ?? uniqid('paytabs_', true);

            // Convert amount from USD to EGP
            $currencyService = app(CurrencyService::class);
            $originalAmount = (float) $paymentData['amount'];
            $convertedAmount = $currencyService->convertCurrency(
                $originalAmount,
                $paymentData['currency'] ?? 'USD',
                $this->currency
            ) ?? $originalAmount;

            $requestPayload = [
                'profile_id' => $this->profileId,
                'tran_type' => 'sale',
                'tran_class' => 'ecom',
                'cart_id' => $cartId,
                'cart_currency' => $this->currency,
                'cart_amount' => $convertedAmount,
                'cart_description' => $paymentData['description'] ?? 'Payment',
                'paypage_lang' => $this->checkoutLang,
                'hide_shipping' => true,
            ];

            // Add item details
            if (! empty($paymentData['items'])) {
                $requestPayload['cart_items'] = array_map(function ($item) use ($currencyService) {
                    $originalPrice = (float) $item['price'];
                    $convertedPrice = $currencyService->convertCurrency(
                        $originalPrice,
                        $paymentData['currency'] ?? 'USD',
                        $this->currency
                    ) ?? $originalPrice;

                    return [
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $convertedPrice,
                        'currency' => $this->currency,
                    ];
                }, $paymentData['items']);
            }

            // Add callback and return URLs
            $verifyUrl = route('payment.verify', [
                'payment_id' => $cartId,
                'gateway' => 'paytabs',
            ]);

            $requestPayload['callback'] = $paymentData['return_url'];
            $requestPayload['return'] = $verifyUrl;

            // Add customer details
            $requestPayload['customer_details'] = [
                'name' => $paymentData['customer_name'] ?? 'Customer',
                'email' => $paymentData['customer_email'],
                'phone' => $paymentData['customer_phone'] ?? '+966500000000',
                'street1' => $paymentData['address']['address1'] ?? 'Not Available',
                'city' => $paymentData['address']['city'] ?? 'Not Available',
                'state' => $paymentData['address']['state'] ?? 'Not Available',
                'country' => $paymentData['country'] ?? 'SA',
                'zip' => $paymentData['address']['zip'] ?? '00000',
                'ip' => $paymentData['ip'] ?? request()->ip(),
            ];

            // Add tokenization support
            $requestPayload['tokenise'] = $paymentData['tokenize'] ?? 1;

            // If using token payment instead of hosted page
            if (isset($paymentData['payment_token'])) {
                $requestPayload['payment_token'] = $paymentData['payment_token'];
            }

            // Add payment methods filter if specified
            if (isset($paymentData['payment_methods'])) {
                $requestPayload['payment_methods'] = $paymentData['payment_methods'];
            }

            $response = Http::withHeaders([
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/payment/request', $requestPayload);

            $responseData = $response->json();

            if (! $response->successful() || ! isset($responseData['redirect_url'])) {
                Log::error('PayTabs Payment Request Failed', [
                    'response' => $responseData,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Payment request failed',
                    'status' => 'failed',
                ];
            }

            // Cache transaction reference for later verification
            if (isset($responseData['tran_ref'])) {
                Cache::put(
                    "paytabs_tran_{$cartId}",
                    $responseData['tran_ref'],
                    now()->addHours(24)
                );
            }

            return [
                'success' => true,
                'message' => 'Redirecting to payment gateway',
                'transaction_id' => $responseData['tran_ref'] ?? null,
                'data' => $responseData,
                'status' => 'pending',
                'redirect_url' => $responseData['redirect_url'],
            ];

        } catch (Exception $e) {
            Log::error('PayTabs Payment Error: '.$e->getMessage(), [
                'payment_data' => $paymentData,
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed',
                'status' => 'failed',
            ];
        }
    }

    /**
     * Handle webhook notifications from the payment gateway (callback)
     */
    public function handleWebhook(Request $request): array
    {
        try {
            $data = $request->all();

            Log::info('PayTabs Webhook Received', ['data' => $data]);

            $tranRef = $data['tran_ref'] ?? null;
            $cartId = $data['cart_id'] ?? null;

            if (! $tranRef && ! $cartId) {
                return [
                    'success' => false,
                    'message' => 'Missing transaction reference',
                    'status' => 'failed',
                ];
            }

            $responseStatus = $data['payment_result']['response_status'] ?? null;
            $responseCode = $data['payment_result']['response_code'] ?? null;
            $responseMessage = $data['payment_result']['response_message'] ?? 'Unknown';

            if ($responseStatus === 'A') {
                return [
                    'success' => true,
                    'message' => 'Payment completed successfully',
                    'transaction_id' => $tranRef,
                    'data' => $data,
                    'status' => 'completed',
                ];
            }

            $status = $this->mapPaytabsResponseStatus($responseStatus);

            return [
                'success' => false,
                'message' => $responseMessage,
                'transaction_id' => $tranRef,
                'data' => $data,
                'status' => $status,
            ];

        } catch (Exception $e) {
            Log::error('PayTabs Webhook Error: '.$e->getMessage(), [
                'request_data' => $request->all(),
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing failed',
                'status' => 'failed',
            ];
        }
    }

    /**
     * Verify the payment status
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            // Get transaction reference from cache if using cart_id
            $tranRef = Cache::get("paytabs_tran_{$paymentId}") ?? $paymentId;

            $response = Http::withHeaders([
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/payment/query', [
                'profile_id' => $this->profileId,
                'tran_ref' => $tranRef,
            ]);

            $responseData = $response->json();

            if (! $response->successful()) {
                Log::error('PayTabs Payment Verification Failed', [
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

            $responseStatus = $responseData['payment_result']['response_status'] ?? null;
            $responseMessage = $responseData['payment_result']['response_message'] ?? 'Unknown';

            if ($responseStatus === 'A') {
                // Clear cache after successful verification
                Cache::forget("paytabs_tran_{$paymentId}");

                return [
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'transaction_id' => $tranRef,
                    'data' => $responseData,
                    'status' => 'completed',
                ];
            }

            $status = $this->mapPaytabsResponseStatus($responseStatus);

            return [
                'success' => false,
                'message' => $responseMessage,
                'transaction_id' => $tranRef,
                'data' => $responseData,
                'status' => $status,
            ];

        } catch (Exception $e) {
            Log::error('PayTabs Verification Error: '.$e->getMessage(), [
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
     * Map PayTabs response status to our internal status
     */
    private function mapPaytabsResponseStatus(?string $responseStatus): string
    {
        return match ($responseStatus) {
            'A' => 'completed',
            'H' => 'pending',
            'P' => 'pending',
            'V' => 'failed',
            'E' => 'failed',
            'D' => 'failed',
            'C' => 'cancelled',
            default => 'unknown',
        };
    }

    /**
     * Issue a refund for a payment
     */
    public function refundPayment(string $paymentId, ?float $amount = null, ?string $reason = null): array
    {
        try {
            $tranRef = Cache::get("paytabs_tran_{$paymentId}") ?? $paymentId;

            // Convert amount from USD to EGP if provided
            $refundAmount = 0;
            if ($amount !== null) {
                $currencyService = app(CurrencyService::class);
                $refundAmount = $currencyService->convertCurrency(
                    $amount,
                    'USD',
                    'EGP'
                ) ?? $amount;
            }

            $requestPayload = [
                'profile_id' => $this->profileId,
                'tran_type' => 'refund',
                'tran_class' => 'ecom',
                'cart_id' => uniqid('refund_', true),
                'cart_currency' => 'EGP',
                'cart_amount' => $refundAmount,
                'cart_description' => $reason ?? 'Refund',
                'tran_ref' => $tranRef,
            ];

            $response = Http::withHeaders([
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/payment/request', $requestPayload);

            $responseData = $response->json();

            if (! $response->successful()) {
                Log::error('PayTabs Refund Failed', [
                    'response' => $responseData,
                    'payment_id' => $paymentId,
                ]);

                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Refund failed',
                    'transaction_id' => $paymentId,
                    'status' => 'failed',
                ];
            }

            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'transaction_id' => $tranRef,
                'refund_id' => $responseData['tran_ref'] ?? null,
                'data' => $responseData,
                'status' => 'refunded',
            ];

        } catch (Exception $e) {
            Log::error('PayTabs Refund Error: '.$e->getMessage(), [
                'payment_id' => $paymentId,
                'amount' => $amount,
            ]);

            return [
                'success' => false,
                'message' => 'Refund failed',
                'transaction_id' => $paymentId,
                'status' => 'failed',
            ];
        }
    }

    /**
     * Get frontend scripts required by this gateway
     */
    public function getScripts(): array
    {
        return [];
    }
}
