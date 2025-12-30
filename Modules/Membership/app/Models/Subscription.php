<?php

declare(strict_types=1);

namespace Modules\Membership\Models;

use Modules\Membership\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Billing\app\Models\Order;
use Modules\Membership\Enums\SubscriptionStatus;
use Modules\Membership\Database\Factories\SubscriptionFactory;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'member_id',
        'plan_id',
        'start_date',
        'end_date',
        'next_billing_date',
        'status',
        'auto_renew',
        'grace_period_days',
        'price_at_subscription',
        'currency',
        'last_payment_id',
        'last_payment_at',
        'activated_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_billing_date' => 'date',
            'status' => SubscriptionStatus::class,
            'auto_renew' => 'boolean',
            'grace_period_days' => 'integer',
            'price_at_subscription' => 'decimal:2',
            'last_payment_at' => 'datetime',
            'activated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Get the member that owns this subscription.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the last payment order.
     */
    public function lastPayment(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'last_payment_id');
    }

    /**
     * Get the user who cancelled this subscription.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the history records for this subscription.
     */
    public function history(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    /**
     * Scope a query to only include expiring subscriptions.
     */
    public function scopeExpiring($query)
    {
        return $query->where('status', SubscriptionStatus::EXPIRING);
    }

    /**
     * Scope a query to only include expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', SubscriptionStatus::EXPIRED);
    }

    /**
     * Scope a query to include subscriptions in grace period.
     */
    public function scopeGracePeriod($query)
    {
        return $query->where('status', SubscriptionStatus::GRACE_PERIOD);
    }

    /**
     * Scope to get subscriptions expiring within X days.
     */
    public function scopeExpiringWithinDays($query, int $days = 7)
    {
        return $query->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>', now())
            ->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::EXPIRING]);
    }

    /**
     * Scope to get subscriptions that should auto-renew soon.
     */
    public function scopeDueForRenewal($query)
    {
        return $query->where('auto_renew', true)
            ->where('next_billing_date', '<=', now()->addDays(3))
            ->where('status', SubscriptionStatus::ACTIVE);
    }

    /**
     * Check if subscription is currently active.
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Check if subscription can be renewed.
     */
    public function canRenew(): bool
    {
        return $this->status->canRenew();
    }

    /**
     * Check if subscription is expiring soon (within 7 days).
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->end_date->diffInDays(now()) <= 7;
    }

    /**
     * Get days remaining in subscription.
     */
    public function daysRemaining(): int
    {
        if ($this->end_date->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->end_date, false);
    }

    /**
     * Get days in grace period remaining.
     */
    public function gracePeriodDaysRemaining(): int
    {
        if ($this->status !== SubscriptionStatus::GRACE_PERIOD) {
            return 0;
        }

        $gracePeriodEnd = $this->end_date->addDays($this->grace_period_days);

        if ($gracePeriodEnd->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($gracePeriodEnd, false);
    }

    /**
     * Check if subscription is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        return $this->status === SubscriptionStatus::GRACE_PERIOD;
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->price_at_subscription, 2) . ' ' . $this->currency;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return SubscriptionFactory::new();
    }
}
