<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentCreated;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Exceptions\PaymentGatewayException;
use Modules\Payment\Models\Payment;

class PaymentService
{
    /**
     * PaymentService constructor with dependency injection.
     */
    public function __construct(
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Get all available payment gateways.
     *
     * @return array
     */
    public function getAvailableGateways()
    {
        return $this->gatewayManager->getGateways(true);
    }

    /**
     * Create a new payment record.
     *
     * @throws PaymentGatewayException
     */
    public function createPayment(array $paymentData, Model $payable, string $gateway): Payment
    {

        // Check if gateway exists and is enabled
        $gatewayInstance = $this->gatewayManager->gateway($gateway);
        if (! $gatewayInstance || ! $gatewayInstance->isEnabled()) {
            throw new PaymentGatewayException("Payment gateway '{$gateway}' is not available.");
        }

        // Create the payment record
        $payment = new Payment;
        $payment->reference = $this->generatePaymentReference();
        $payment->amount = $paymentData['amount'];
        $payment->currency = $paymentData['currency'] ?? config('payment.default_currency', 'SAR');
        $payment->gateway = $gateway;
        $payment->status = Payment::STATUS_PENDING;
        $payment->customer_email = $paymentData['customer_email'] ?? null;
        $payment->customer_name = $paymentData['customer_name'] ?? null;
        $payment->metadata = $paymentData['metadata'] ?? [];

        // Associate with the payable entity
        $payment->payable()->associate($payable);
        $payment->save();

        // Dispatch payment created event
        event(new PaymentCreated($payment));

        return $payment;
    }

    /**
     * Process a payment using the specified gateway.
     *
     * @throws PaymentGatewayException
     */
    public function processPayment(Payment $payment, array $additionalData = []): array
    {
        //        try {
        // Update payment status to processing
        $payment->status = Payment::STATUS_PROCESSING;
        $payment->save();

        // Get the gateway instance
        $gateway = $this->gatewayManager->gateway($payment->gateway);
        if (! $gateway) {
            throw new PaymentGatewayException("Payment gateway '{$payment->gateway}' not found.");
        }

        // Prepare payment data for the gateway
        $paymentData = [
            'amount' => $payment->amount,
            'reference' => $payment->reference,
            'currency' => $payment->currency,
            'description' => 'Payment #'.$payment->reference,
            'metadata' => array_merge($payment->metadata ?? [], [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
            ]),
            'customer_email' => $payment->customer_email,
            'customer_name' => $payment->customer_name,
            'return_url' => route('payment.success', ['order' => $payment->payable->id]),
        ];
        // if $payment->amount is zero, skip gateway processing
        if ($payment->amount <= 0) {
            $payment->status = Payment::STATUS_COMPLETED;
            $payment->gateway_data = array_merge($payment->gateway_data ?? [], [
                'note' => 'Zero amount payment, marked as completed.',
            ]);
            $payment->save();
            $payment->payable->handlePaymentCompleted($payment);
            event(new PaymentCompleted($payment));

            return [
                'success' => true,
                'message' => 'Payment completed for zero amount',
                'status' => Payment::STATUS_COMPLETED,
                'data' => null,
            ];
        }

        // Merge additional data provided by the form
        $paymentData = array_merge($paymentData, $additionalData);
        // Process payment with gateway
        $result = $gateway->processPayment($paymentData);

        // Update payment with gateway response
        if (isset($result['transaction_id'])) {
            $payment->gateway_payment_id = $result['transaction_id'];
        }

        $payment->gateway_data = array_merge($payment->gateway_data ?? [], [
            'gateway_response' => $result['data'] ?? null,
        ]);

        // Update payment status based on result
        if ($result['success']) {
            if ($result['status'] === 'completed') {
                $payment->status = Payment::STATUS_COMPLETED;
                $payment->payable->handlePaymentCompleted($payment);
                event(new PaymentCompleted($payment));
            } else {
                $payment->status = Payment::STATUS_PROCESSING;
            }
        } else {
            $payment->status = Payment::STATUS_FAILED;
            $payableModel = $payment->payable;
            $payableModel->handlePaymentFailed($payment, $result['message'] ?? 'Payment declined');
            event(new PaymentFailed($payment, $result['message'] ?? 'Payment processing failed'));
        }

        $payment->save();

        return $result;
        //        } catch (\Exception $e) {
        //            dd($e->getMessage());
        //            Log::error('Payment processing error: '.$e->getMessage(), [
        //                'payment_id' => $payment->id,
        //                'gateway' => $payment->gateway,
        //            ]);
        //
        //            // Update payment status to fail
        //            $payment->status = Payment::STATUS_FAILED;
        //            $payment->gateway_data = array_merge($payment->gateway_data ?? [], [
        //                'error' => $e->getMessage(),
        //            ]);
        //            $payment->save();
        //
        //            // Dispatch payment failed event
        //            event(new PaymentFailed($payment, $e->getMessage()));
        //
        //            throw new PaymentGatewayException('Payment processing failed: '.$e->getMessage());
        //        }
    }

    /**
     * Verify a payment's status with its gateway.
     *
     * @throws PaymentGatewayException
     */
    public function verifyPayment(string $reference): array
    {
        // Find payment by reference
        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            throw new PaymentGatewayException("Payment with reference '{$reference}' not found.");
        }

        // Get gateway
        $gateway = $this->gatewayManager->gateway($payment->gateway);
        if (! $gateway) {
            throw new PaymentGatewayException("Payment gateway '{$payment->gateway}' not found.");
        }

        // Verify with gateway if we have a gateway payment ID
        if ($payment->gateway_payment_id) {
            $result = $gateway->verifyPayment($payment->gateway_payment_id);

            // Update payment status based on verification result
            if ($result['success'] && $result['status'] === 'completed' && $payment->status !== Payment::STATUS_COMPLETED) {
                $payment->status = Payment::STATUS_COMPLETED;
                $payment->gateway_data = array_merge($payment->gateway_data ?? [], [
                    'verification_data' => $result['data'] ?? null,
                ]);
                $payment->save();

                // Dispatch payment completed event
                event(new PaymentCompleted($payment));
            }

            return $result;
        }

        return [
            'success' => false,
            'message' => 'No gateway payment ID available for verification',
            'status' => $payment->status,
        ];
    }

    /**
     * Generate a unique payment reference.
     */
    protected function generatePaymentReference(): string
    {
        $prefix = config('payment.reference_prefix', 'PAY');
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
