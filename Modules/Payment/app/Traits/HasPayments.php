<?php

namespace Modules\Payment\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Payment\Models\Payment;

trait HasPayments
{
    /**
     * Get all payments for this model.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Get completed payments for this model.
     */
    public function completedPayments(): MorphMany
    {
        return $this->payments()->where('status', Payment::STATUS_COMPLETED);
    }

    /**
     * Check if this entity has at least one completed payment.
     */
    public function isPaid(): bool
    {
        return $this->completedPayments()->exists();
    }

    /**
     * Get the total amount paid for this entity.
     */
    public function getTotalPaidAmount(): float
    {
        return (float) $this->completedPayments()->sum('amount');
    }

    /**
     * Get the remaining balance to be paid.
     */
    public function getRemainingBalance(): float
    {
        $totalAmount = $this->getPaymentAmount();
        $paidAmount = $this->getTotalPaidAmount();

        return max(0, $totalAmount - $paidAmount);
    }

    /**
     * Check if the entity is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->getRemainingBalance() <= 0;
    }

    /**
     * Get the latest payment for this entity.
     */
    public function getLatestPayment(): ?Payment
    {
        return $this->payments()->latest()->first();
    }

    /**
     * Get the payment currency for this payable entity.
     * Override this method in your model if needed.
     */
    public function getPaymentCurrency(): string
    {
        return $this->currency ?? config('payment.default_currency', 'USD');
    }

    /**
     * Get the payment description for this payable entity.
     * Override this method in your model if needed.
     */
    public function getPaymentDescription(): string
    {
        $modelName = class_basename($this);

        return "Payment for {$modelName} #{$this->id}";
    }

    /**
     * Handle the "payment completed" event.
     *
     * This method is meant to be overridden in the model to provide custom
     * behavior when a payment is marked as completed.
     * For example, notifications to the customer can be sent here.
     *
     * @param  Payment  $payment  The payment instance that triggered the event.
     */
    public function handlePaymentCompleted(Payment $payment): void
    {
        // The default implementation does nothing
        // Override this method in your model to add custom behavior

        // Send a notification to the customer
        // $payment->sendNotificationToCustomer();

    }

    /**
     * Default implementation of a payment failure handler.
     * Override this method in your model to add custom behavior.
     */
    public function handlePaymentFailed(Payment $payment, string $reason): void
    {
        // The default implementation does nothing
        // Override this method in your model to add custom behavior
    }

    /**
     * Get the total amount due for this payable entity.
     * Override this method in your model if needed.
     */
    public function getPaymentAmount(): float
    {
        // Look for common attribute names that might contain the payment amount
        foreach (['amount', 'total', 'price', 'cost'] as $attribute) {
            if (isset($this->$attribute) && is_numeric($this->$attribute)) {
                return (float) $this->$attribute;
            }
        }

        // Default to 0 if no amount attribute is found
        return 0.0;
    }
}
