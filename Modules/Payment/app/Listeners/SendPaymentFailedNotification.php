<?php

declare(strict_types=1);

namespace Modules\Payment\Listeners;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Payment\Events\PaymentFailed;

class SendPaymentFailedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;
        $reason = $event->reason;

        // Log the payment failure
        Log::warning('Payment failed', [
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => $payment->gateway,
            'reason' => $reason,
            'payable_type' => $payment->payable_type,
            'payable_id' => $payment->payable_id,
        ]);

        // Check if customer email is available
        if ($payment->customer_email) {
            try {
                // In a real implementation, you would send an email notification
                // For now, we'll just log that an email would be sent
                Log::info('Payment failure email would be sent', [
                    'to' => $payment->customer_email,
                    'reference' => $payment->reference,
                    'reason' => $reason,
                ]);

                /*
                // Example of sending an email
                Mail::to($payment->customer_email)->send(
                    new PaymentFailedMail($payment, $reason)
                );
                */
            } catch (Exception $e) {
                Log::error('Failed to send payment failure email', [
                    'error' => $e->getMessage(),
                    'payment_reference' => $payment->reference,
                ]);
            }
        }

        // Also notify administrators about payment failures
        try {
            $adminEmail = config('payment.admin_notifications.email');

            if ($adminEmail) {
                Log::info('Payment failure admin notification would be sent', [
                    'to' => $adminEmail,
                    'reference' => $payment->reference,
                ]);

                /*
                // Example of sending an admin notification email
                Mail::to($adminEmail)->send(
                    new AdminPaymentFailureNotification($payment, $reason)
                );
                */
            }
        } catch (Exception $e) {
            Log::error('Failed to send admin payment failure notification', [
                'error' => $e->getMessage(),
                'payment_reference' => $payment->reference,
            ]);
        }
    }
}
