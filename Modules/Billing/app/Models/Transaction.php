<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Transaction Model - Records financial transactions.
 *
 * Trace: SRS-FR-BILLING-003 (Transaction Management)
 */
class Transaction extends Model
{
    use HasFactory;

    // Transaction statuses
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'payable_id',
        'payable_type',
        'amount',
        'paid_amount',
        'currency',
        'reference',
        'gateway',
        'gateway_response',
        'status',
        'completed_at',
        'meta',
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
            'paid_amount' => 'decimal:2',
            'completed_at' => 'datetime',
            'meta' => 'array',
            'gateway_response' => 'array',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the related item (order or invoice).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user associated with this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope for completed transactions.
     *
     * @param Builder<Transaction> $query
     * @return Builder<Transaction>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed transactions.
     *
     * @param Builder<Transaction> $query
     * @return Builder<Transaction>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for refunded transactions.
     *
     * @param Builder<Transaction> $query
     * @return Builder<Transaction>
     */
    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    /**
     * Scope for pending transactions.
     *
     * @param Builder<Transaction> $query
     * @return Builder<Transaction>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // ========================================
    // STATUS OPERATIONS
    // ========================================

    /**
     * Mark the transaction as completed.
     */
    public function markCompleted(): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark the transaction as failed.
     */
    public function markFailed(?string $reason = null): self
    {
        $this->status = self::STATUS_FAILED;

        if ($reason) {
            $meta = $this->meta ?? [];
            $meta['failure_reason'] = $reason;
            $this->meta = $meta;
        }

        $this->save();

        return $this;
    }

    /**
     * Mark the transaction as refunded.
     */
    public function markRefunded(): self
    {
        $this->status = self::STATUS_REFUNDED;
        $this->save();

        return $this;
    }

    // ========================================
    // STATUS CHECKS
    // ========================================

    /**
     * Check if transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
