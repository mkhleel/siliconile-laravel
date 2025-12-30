<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentGatewayManager;

class PaymentVerificationController extends Controller
{
    /**
     * Verify payment after redirect from payment gateway.
     */
    public function verify(Request $request, string $gateway)
    {
        Log::info('Payment verification redirect received', [
            'gateway' => $gateway,
            'params' => $request->all(),
        ]);

        try {
            // Get the gateway instance
            $gatewayManager = app(PaymentGatewayManager::class);
            $gatewayInstance = $gatewayManager->gateway($gateway);

            if (! $gatewayInstance) {
                Log::warning('Unknown payment gateway for verification', ['gateway' => $gateway]);

                return redirect()->route('payment.error')
                    ->with('error', 'Unknown payment gateway');
            }

            // Get payment ID from request - support multiple gateway parameter names
            $paymentId = $request->get('payment_id')
                ?? $request->get('cart_id')
                ?? $request->get('tran_ref')
                ?? $request->get('sessionId')      // Kashier session ID
                ?? $request->get('orderId')        // Kashier order ID
                ?? $request->get('merchantOrderId'); // Kashier merchant order ID

            if (! $paymentId) {
                Log::warning('No payment ID provided in verification request', [
                    'gateway' => $gateway,
                    'params' => $request->all(),
                ]);

                return redirect()->route('payment.error')
                    ->with('error', 'Invalid payment reference');
            }

            // Verify the payment through the gateway
            $result = $gatewayInstance->verifyPayment($paymentId);

            Log::info('Payment verification result', [
                'gateway' => $gateway,
                'payment_id' => $paymentId,
                'result' => $result,
            ]);

            // Find the payment record by gateway payment ID or cart ID
            $payment = Payment::where('gateway_payment_id', $paymentId)
                ->where('gateway', $gateway)
                ->first();

            if (! $payment) {
                // Try to find by transaction reference if provided in result
                if (isset($result['transaction_id'])) {
                    $payment = Payment::where('gateway_payment_id', $result['transaction_id'])
                        ->where('gateway', $gateway)
                        ->first();
                }
            }

            if (! $payment) {
                // Try to find by payment reference (for Kashier merchantOrderId)
                $payment = Payment::where('reference', $paymentId)
                    ->where('gateway', $gateway)
                    ->first();
            }

            if (! $payment && isset($result['order_id'])) {
                // Try by order_id from result (Kashier merchantOrderId)
                $payment = Payment::where('reference', $result['order_id'])
                    ->where('gateway', $gateway)
                    ->first();
            }

            // Update payment status based on verification result
            if ($payment && isset($result['status'])) {
                $previousStatus = $payment->status;

                // Update payment status
                if ($result['status'] === 'completed' && $result['success']) {
                    $payment->status = Payment::STATUS_COMPLETED;
                    $payment->metadata = array_merge($payment->metadata ?? [], [
                        'completed_at' => now()->toISOString(),
                        'verification_data' => $result['data'] ?? [],
                    ]);

                    // Store gateway transaction ID if available
                    if (isset($result['transaction_id'])) {
                        $payment->gateway_payment_id = $result['transaction_id'];
                    }

                    $payment->save();

                    // Only trigger completion handler if status changed
                    if ($previousStatus !== Payment::STATUS_COMPLETED) {
                        // Get the payable model (e.g., Subscription, Order)
                        $payableModel = $payment->payable;

                        if ($payableModel) {
                            $payableModel->handlePaymentCompleted($payment);
                        }
                    }

                    // Redirect to success page with order ID if available
                    if ($payment->payable_type === 'Modules\\Billing\\Models\\Order') {
                        return redirect()->route('payment.success', ['order' => $payment->payable_id])
                            ->with('success', 'Payment completed successfully');
                    }

                    return redirect()->route('payment.success', ['order' => $payment->id])
                        ->with('success', 'Payment completed successfully');

                } elseif ($result['status'] === 'failed') {
                    $payment->status = Payment::STATUS_FAILED;
                    $payment->metadata = array_merge($payment->metadata ?? [], [
                        'failed_at' => now()->toISOString(),
                        'error_message' => $result['message'] ?? 'Payment failed',
                        'verification_data' => $result['data'] ?? [],
                    ]);
                    $payment->save();

                    // Trigger failure handler
                    $payableModel = $payment->payable;
                    if ($payableModel) {
                        $payableModel->handlePaymentFailed(
                            $payment,
                            $result['message'] ?? 'Payment verification failed'
                        );
                    }

                    return redirect()->route('payment.failed')
                        ->with('error', $result['message'] ?? 'Payment failed');

                } elseif ($result['status'] === 'cancelled') {
                    $payment->status = Payment::STATUS_CANCELLED;
                    $payment->metadata = array_merge($payment->metadata ?? [], [
                        'cancelled_at' => now()->toISOString(),
                        'verification_data' => $result['data'] ?? [],
                    ]);
                    $payment->save();

                    return redirect()->route('payment.cancelled')
                        ->with('info', 'Payment was cancelled');

                } elseif ($result['status'] === 'pending') {
                    $payment->status = Payment::STATUS_PENDING;
                    $payment->metadata = array_merge($payment->metadata ?? [], [
                        'verification_data' => $result['data'] ?? [],
                    ]);
                    $payment->save();

                    return redirect()->route('payment.error')
                        ->with('warning', 'Payment is still pending');
                }
            }

            // If no payment record found or status not handled
            if ($result['success']) {
                return redirect()->route('payment.success', ['order' => $paymentId])
                    ->with('success', 'Payment completed successfully');
            }

            return redirect()->route('payment.failed')
                ->with('error', $result['message'] ?? 'Payment verification failed');

        } catch (Exception $e) {
            Log::error('Payment Verification Error: '.$e->getMessage(), [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('payment.error')
                ->with('error', 'An error occurred while verifying your payment');
        }
    }
}
