<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Plan;
use Modules\Membership\Models\Subscription;
use Modules\SpaceBooking\Enums\ResourceType;

/**
 * BookingCredit Model - Tracks allocated and used booking credits per member.
 */
class BookingCredit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'booking_credits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'member_id',
        'resource_type',
        'period_start',
        'period_end',
        'allocated_credits',
        'used_credits',
        'plan_id',
        'subscription_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resource_type' => ResourceType::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'allocated_credits' => 'decimal:2',
            'used_credits' => 'decimal:2',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the member who owns these credits.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the plan that granted these credits.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the subscription associated with these credits.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // ========================================
    // CREDIT OPERATIONS
    // ========================================

    /**
     * Get remaining available credits.
     */
    public function getRemainingCredits(): float
    {
        return max(0, (float) $this->allocated_credits - (float) $this->used_credits);
    }

    /**
     * Check if there are available credits.
     */
    public function hasAvailableCredits(): bool
    {
        return $this->getRemainingCredits() > 0;
    }

    /**
     * Use credits (deduct from available).
     */
    public function useCredits(float $amount): bool
    {
        if ($amount > $this->getRemainingCredits()) {
            return false;
        }

        $this->used_credits = (float) $this->used_credits + $amount;
        return $this->save();
    }

    /**
     * Refund credits (add back to available).
     */
    public function refundCredits(float $amount): bool
    {
        $this->used_credits = max(0, (float) $this->used_credits - $amount);
        return $this->save();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to credits for a specific member.
     */
    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope to credits for a specific resource type.
     */
    public function scopeForResourceType(Builder $query, ResourceType $type): Builder
    {
        return $query->where('resource_type', $type->value);
    }

    /**
     * Scope to currently active credits (within period).
     */
    public function scopeActive(Builder $query, ?Carbon $date = null): Builder
    {
        $date = $date ?? now();

        return $query->where('period_start', '<=', $date)
            ->where('period_end', '>=', $date);
    }

    /**
     * Scope to credits with remaining balance.
     */
    public function scopeWithAvailableCredits(Builder $query): Builder
    {
        return $query->whereColumn('used_credits', '<', 'allocated_credits');
    }

    // ========================================
    // STATIC HELPERS
    // ========================================

    /**
     * Get current available credits for a member and resource type.
     */
    public static function getAvailableForMember(
        int $memberId,
        ResourceType $resourceType,
        ?Carbon $date = null
    ): float {
        $date = $date ?? now();

        return (float) self::query()
            ->forMember($memberId)
            ->forResourceType($resourceType)
            ->active($date)
            ->withAvailableCredits()
            ->sum(DB::raw('allocated_credits - used_credits'));
    }

    /**
     * Get or create credit record for a member's current period.
     */
    public static function getOrCreateForPeriod(
        int $memberId,
        ResourceType $resourceType,
        Carbon $periodStart,
        Carbon $periodEnd,
        float $allocatedCredits,
        ?int $planId = null,
        ?int $subscriptionId = null
    ): self {
        return self::firstOrCreate(
            [
                'member_id' => $memberId,
                'resource_type' => $resourceType->value,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            [
                'allocated_credits' => $allocatedCredits,
                'used_credits' => 0,
                'plan_id' => $planId,
                'subscription_id' => $subscriptionId,
            ]
        );
    }
}
