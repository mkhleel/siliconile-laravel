<?php

declare(strict_types=1);

namespace Modules\Payment\Listeners;

use Modules\Payment\Events\PaymentFailed;

class HandleFailedPaymentListener
{

    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;
        $reason = $event->reason;

        // Update payment status if not already done
        if ($payment->status !== 'failed') {
            $payment->status = 'failed';
            $payment->error_message = $reason;
            $payment->save();
        }

        // Call the failure handler on the related model
        $payment->payable->handlePaymentFailed($payment, $reason);
    }
}
