<?php

declare(strict_types=1);

namespace Modules\Payment\Listeners;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Payment\Events\PaymentCompleted;

class SendPaymentCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        // Log the payment completion
        Log::info('Payment completed', [
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => $payment->gateway,
            'payable_type' => $payment->payable_type,
            'payable_id' => $payment->payable_id,
        ]);

        // Check if customer email is available
        if ($payment->customer_email) {
            try {
                // In a real implementation, you would send an email notification
                // For now, we'll just log that an email would be sent
                Log::info('Payment completion email would be sent', [
                    'to' => $payment->customer_email,
                    'reference' => $payment->reference,
                ]);

                /*
                // Example of sending an email
                Mail::to($payment->customer_email)->send(
                    new PaymentCompletedMail($payment)
                );
                */
            } catch (Exception $e) {
                Log::error('Failed to send payment completion email', [
                    'error' => $e->getMessage(),
                    'payment_reference' => $payment->reference,
                ]);
            }
        }
    }
}
