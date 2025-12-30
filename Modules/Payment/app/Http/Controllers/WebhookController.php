<?php

namespace Modules\Payment\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentGatewayManager;

class WebhookController extends Controller
{
    /**
     * Handle a payment gateway webhook request.
     *
     * @return Response
     */
    public function handleWebhook(Request $request, string $gateway)
    {
        Log::info('Webhook received', [
            'gateway' => $gateway,
            'ip' => $request->ip(),
        ]);

        try {
            // Get the gateway instance
            $gatewayManager = app(PaymentGatewayManager::class);
            $gatewayInstance = $gatewayManager->gateway($gateway);

            if (! $gatewayInstance) {
                Log::warning('Unknown payment gateway', ['gateway' => $gateway]);

                return response('Unknown payment gateway', 404);
            }

            // Process the webhook through the gateway
            $result = $gatewayInstance->handleWebhook($request);

            // Update payment status if transaction ID is provided
            if (isset($result['transaction_id'])) {
                $payment = Payment::where('gateway_payment_id', $result['transaction_id'])
                    ->where('gateway', $gateway)
                    ->first();

                // Also try finding by order_id (merchantOrderId) for Kashier
                if (! $payment && isset($result['order_id'])) {
                    $payment = Payment::where('reference', $result['order_id'])
                        ->where('gateway', $gateway)
                        ->first();
                }

                if ($payment) {
                    if ($result['status'] === 'completed' || $result['status'] === 'succeeded') {
                        // Update payment status in your database
                        $payment->status = Payment::STATUS_COMPLETED;
                        $payment->metadata = array_merge($payment->metadata ?? [], ['completed_at' => now()]);

                        // Store webhook data in the payment record
                        if (isset($result['data'])) {
                            $payment->gateway_data = array_merge(
                                $payment->gateway_data ?? [],
                                ['webhook_data' => $result['data']]
                            );
                        }

                        $payment->save();

                        // Get the payable model (e.g., Subscription, Order)
                        $payableModel = $payment->payable;

                        // Call the handlePaymentCompleted method on the model
                        if ($payableModel && method_exists($payableModel, 'handlePaymentCompleted')) {
                            $payableModel->handlePaymentCompleted($payment);
                        }
                    }

                    if ($result['status'] === 'failed') {
                        // Update payment status
                        $payment->status = Payment::STATUS_FAILED;
                        $payment->metadata = array_merge(
                            $payment->metadata ?? [],
                            ['error_message' => $result['message'] ?? 'Unknown error']
                        );
                        $payment->save();

                        // Get the model this payment is for
                        $payableModel = $payment->payable;

                        // Call the failure handler with the reason
                        if ($payableModel && method_exists($payableModel, 'handlePaymentFailed')) {
                            $payableModel->handlePaymentFailed(
                                $payment,
                                $result['message'] ?? 'Payment declined by gateway'
                            );
                        }
                    }

                }

            }

            // Return the appropriate response
            return response($result['success'] ? 'Webhook processed successfully' : 'Webhook processing failed',
                $result['success'] ? 200 : 422);

        } catch (Exception $e) {
            Log::error('Webhook Error: '.$e->getMessage(), [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return response('Webhook error: '.$e->getMessage(), 500);
        }
    }
}
