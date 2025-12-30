<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Membership\Models\Member;
use Modules\SpaceBooking\Enums\BookingStatus;
use Modules\SpaceBooking\Enums\PaymentStatus;
use Modules\SpaceBooking\Enums\PriceUnit;

/**
 * Booking Model - Represents a time-based reservation of a space resource.
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'space_bookings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'booking_code',
        'space_resource_id',
        'bookable_type',
        'bookable_id',
        'start_time',
        'end_time',
        'checked_in_at',
        'checked_out_at',
        'status',
        'unit_price',
        'price_unit',
        'quantity',
        'discount_amount',
        'total_price',
        'currency',
        'credits_used',
        'notes',
        'admin_notes',
        'cancellation_reason',
        'cancelled_at',
        'attendees_count',
        'parent_booking_id',
        'payment_status',
        'order_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'status' => BookingStatus::class,
            'unit_price' => 'decimal:2',
            'price_unit' => PriceUnit::class,
            'quantity' => 'integer',
            'discount_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'credits_used' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'attendees_count' => 'integer',
            'payment_status' => PaymentStatus::class,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $booking) {
            if (empty($booking->booking_code)) {
                $booking->booking_code = (string) Str::uuid();
            }
        });
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the resource being booked.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(SpaceResource::class, 'space_resource_id');
    }

    /**
     * Alias for resource() to match spaceResource naming.
     */
    public function spaceResource(): BelongsTo
    {
        return $this->resource();
    }

    /**
     * Get the entity who made the booking (User or Member).
     */
    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent booking (if this is part of a recurring series).
     */
    public function parentBooking(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_booking_id');
    }

    /**
     * Get child bookings (recurring series).
     */
    public function childBookings(): HasMany
    {
        return $this->hasMany(self::class, 'parent_booking_id');
    }

    // ========================================
    // STATUS TRANSITIONS
    // ========================================

    /**
     * Confirm the booking.
     */
    public function confirm(): bool
    {
        if (!$this->status->canConfirm()) {
            return false;
        }

        $this->status = BookingStatus::CONFIRMED;
        return $this->save();
    }

    /**
     * Cancel the booking.
     */
    public function cancel(?string $reason = null): bool
    {
        if (!$this->status->canCancel()) {
            return false;
        }

        $this->status = BookingStatus::CANCELLED;
        $this->cancellation_reason = $reason;
        $this->cancelled_at = now();
        return $this->save();
    }

    /**
     * Mark booking as completed.
     */
    public function complete(): bool
    {
        if (!$this->status->canComplete()) {
            return false;
        }

        $this->status = BookingStatus::COMPLETED;
        if (!$this->checked_out_at) {
            $this->checked_out_at = now();
        }
        return $this->save();
    }

    /**
     * Mark booking as no-show.
     */
    public function markNoShow(): bool
    {
        if ($this->status !== BookingStatus::CONFIRMED) {
            return false;
        }

        $this->status = BookingStatus::NO_SHOW;
        return $this->save();
    }

    /**
     * Check in to the booking.
     */
    public function checkIn(): bool
    {
        if ($this->status !== BookingStatus::CONFIRMED) {
            return false;
        }

        $this->checked_in_at = now();
        return $this->save();
    }

    /**
     * Check out from the booking.
     */
    public function checkOut(): bool
    {
        if (!$this->checked_in_at) {
            return false;
        }

        $this->checked_out_at = now();
        return $this->complete();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to bookings that block time slots.
     */
    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (BookingStatus $status) => $status->value,
            BookingStatus::blockingStatuses()
        ));
    }

    /**
     * Scope to upcoming bookings.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_time', '>', now());
    }

    /**
     * Scope to past bookings.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('end_time', '<', now());
    }

    /**
     * Scope to bookings on a specific date.
     */
    public function scopeOnDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('start_time', $date);
    }

    /**
     * Scope to bookings within a date range.
     */
    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where('start_time', '>=', $start)
            ->where('end_time', '<=', $end);
    }

    /**
     * Scope to bookings for a specific resource.
     */
    public function scopeForResource(Builder $query, int $resourceId): Builder
    {
        return $query->where('space_resource_id', $resourceId);
    }

    /**
     * Scope to bookings by status.
     */
    public function scopeWithStatus(Builder $query, BookingStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to bookings for a specific user or member.
     */
    public function scopeForBookable(Builder $query, Model $bookable): Builder
    {
        return $query->where('bookable_type', $bookable->getMorphClass())
            ->where('bookable_id', $bookable->getKey());
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Get duration in minutes.
     */
    public function getDurationMinutes(): int
    {
        return (int) $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get duration formatted for display.
     */
    public function getDurationFormatted(): string
    {
        $minutes = $this->getDurationMinutes();

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours . ' hr' . ($hours > 1 ? 's' : '');
        }

        return $hours . ' hr ' . $remainingMinutes . ' min';
    }

    /**
     * Check if booking is currently active (ongoing).
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->status === BookingStatus::CONFIRMED
            && $this->start_time <= $now
            && $this->end_time >= $now;
    }

    /**
     * Check if booking can be modified.
     */
    public function canModify(): bool
    {
        // Can't modify past or cancelled bookings
        if ($this->end_time < now() || $this->status === BookingStatus::CANCELLED) {
            return false;
        }

        // Can't modify if already checked in
        return $this->checked_in_at === null;
    }

    /**
     * Get the booker's name for display.
     */
    public function getBookerName(): string
    {
        if ($this->bookable instanceof User) {
            return $this->bookable->name;
        }

        if ($this->bookable instanceof Member) {
            return $this->bookable->user?->name ?? 'Unknown Member';
        }

        return 'Unknown';
    }

    /**
     * Get formatted total price.
     */
    public function getFormattedTotalPrice(): string
    {
        return number_format((float) $this->total_price, 2) . ' ' . $this->currency;
    }
}
