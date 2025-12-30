<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Payment Model - Represents a payment transaction.
 *
 * Trace: SRS-FR-PAYMENT-001 (Payment Management)
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'payments';

    /**
     * The possible payment statuses.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'reference',
        'amount',
        'currency',
        'gateway',
        'gateway_payment_id',
        'gateway_data',
        'status',
        'payable_type',
        'payable_id',
        'customer_email',
        'customer_name',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_data' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Bootstrap the model.
     */
    protected static function booted(): void
    {
        static::updated(function (Payment $payment): void {
            // Check if payment status was changed to completed
            if (
                $payment->status === self::STATUS_COMPLETED &&
                $payment->getOriginal('status') !== self::STATUS_COMPLETED
            ) {
                $payableModel = $payment->payable;

                if ($payableModel && method_exists($payableModel, 'handlePaymentCompleted')) {
                    $payableModel->handlePaymentCompleted($payment);
                }
            }

            // Check if the payment status was changed to 'failed'
            if (
                $payment->status === self::STATUS_FAILED &&
                $payment->getOriginal('status') !== self::STATUS_FAILED
            ) {
                $payableModel = $payment->payable;

                if ($payableModel && method_exists($payableModel, 'handlePaymentFailed')) {
                    $reason = $payment->metadata['error_message'] ?? 'Unknown error';
                    $payableModel->handlePaymentFailed($payment, $reason);
                }
            }
        });
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the payable entity that the payment belongs to.
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the refunds for this payment.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope a query to only include payments with a specific status.
     *
     * @param Builder<Payment> $query
     * @return Builder<Payment>
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for completed payments.
     *
     * @param Builder<Payment> $query
     * @return Builder<Payment>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending payments.
     *
     * @param Builder<Payment> $query
     * @return Builder<Payment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed payments.
     *
     * @param Builder<Payment> $query
     * @return Builder<Payment>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ========================================
    // STATUS CHECKS
    // ========================================

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if payment is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Check if payment is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    // ========================================
    // STATUS OPERATIONS
    // ========================================

    /**
     * Mark the payment as completed.
     */
    public function markCompleted(?string $gatewayPaymentId = null): self
    {
        $this->status = self::STATUS_COMPLETED;

        if ($gatewayPaymentId) {
            $this->gateway_payment_id = $gatewayPaymentId;
        }

        $this->save();

        return $this;
    }

    /**
     * Mark the payment as failed.
     */
    public function markFailed(?string $reason = null): self
    {
        $this->status = self::STATUS_FAILED;

        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata['error_message'] = $reason;
            $this->metadata = $metadata;
        }

        $this->save();

        return $this;
    }

    /**
     * Mark the payment as refunded.
     */
    public function markRefunded(): self
    {
        $this->status = self::STATUS_REFUNDED;
        $this->save();

        return $this;
    }
}
