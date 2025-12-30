<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\SpaceBooking\Enums\BookingStatus;
use Modules\SpaceBooking\Enums\PriceUnit;
use Modules\SpaceBooking\Enums\ResourceType;

/**
 * SpaceResource Model - Represents bookable spaces (rooms, desks, offices).
 *
 * Uses Single Table Inheritance pattern with `resource_type` discriminator
 * and `attributes` JSON for type-specific data flexibility.
 */
class SpaceResource extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'space_resources';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'resource_type',
        'description',
        'capacity',
        'location',
        'image',
        'hourly_rate',
        'daily_rate',
        'monthly_rate',
        'currency',
        'buffer_minutes',
        'available_from',
        'available_until',
        'min_booking_minutes',
        'max_booking_minutes',
        'attributes',
        'pricing_rules',
        'is_active',
        'requires_approval',
        'sort_order',
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
            'capacity' => 'integer',
            'hourly_rate' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'monthly_rate' => 'decimal:2',
            'buffer_minutes' => 'integer',
            'available_from' => 'datetime:H:i',
            'available_until' => 'datetime:H:i',
            'min_booking_minutes' => 'integer',
            'max_booking_minutes' => 'integer',
            'attributes' => 'array',
            'pricing_rules' => 'array',
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $resource) {
            if (empty($resource->slug)) {
                $resource->slug = Str::slug($resource->name);
            }
        });
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get all bookings for this resource.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'space_resource_id');
    }

    /**
     * Get amenities attached to this resource.
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            ResourceAmenity::class,
            'space_resource_amenity',
            'space_resource_id',
            'resource_amenity_id'
        );
    }

    // ========================================
    // AVAILABILITY LOGIC (CRITICAL)
    // ========================================

    /**
     * Check if the resource is available for booking at the given time range.
     *
     * This is the CORE availability check that prevents double-bookings.
     * It considers:
     * 1. Existing confirmed/pending bookings
     * 2. Buffer time between bookings
     * 3. Operating hours
     */
    public function isAvailable(Carbon $startTime, Carbon $endTime): bool
    {
        // Check operating hours first
        if (!$this->isWithinOperatingHours($startTime, $endTime)) {
            return false;
        }

        // Check for booking duration limits
        $durationMinutes = $startTime->diffInMinutes($endTime);
        if ($durationMinutes < $this->min_booking_minutes) {
            return false;
        }
        if ($this->max_booking_minutes && $durationMinutes > $this->max_booking_minutes) {
            return false;
        }

        // Check for overlapping bookings (with buffer time)
        return !$this->hasOverlappingBooking($startTime, $endTime);
    }

    /**
     * Check if there's an overlapping booking (including buffer time).
     *
     * The overlap detection query uses the principle:
     * Two time ranges [A_start, A_end] and [B_start, B_end] overlap if:
     * A_start < B_end AND A_end > B_start
     *
     * With buffer time, we expand the existing booking's range.
     */
    public function hasOverlappingBooking(Carbon $startTime, Carbon $endTime, ?int $excludeBookingId = null): bool
    {
        $bufferMinutes = $this->buffer_minutes ?? 0;

        // Expand the requested time slot by buffer to prevent conflicts
        $checkStart = $startTime->copy()->subMinutes($bufferMinutes);
        $checkEnd = $endTime->copy()->addMinutes($bufferMinutes);

        $query = $this->bookings()
            ->whereIn('status', array_map(
                fn (BookingStatus $status) => $status->value,
                BookingStatus::blockingStatuses()
            ))
            // Overlap condition: existing.start < check.end AND existing.end > check.start
            ->where('start_time', '<', $checkEnd)
            ->where('end_time', '>', $checkStart);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Check if the requested time is within operating hours.
     */
    public function isWithinOperatingHours(Carbon $startTime, Carbon $endTime): bool
    {
        // If no operating hours set, resource is available 24/7
        if (!$this->available_from || !$this->available_until) {
            return true;
        }

        $availableFrom = Carbon::parse($this->available_from)->format('H:i');
        $availableUntil = Carbon::parse($this->available_until)->format('H:i');

        $startTimeOfDay = $startTime->format('H:i');
        $endTimeOfDay = $endTime->format('H:i');

        return $startTimeOfDay >= $availableFrom && $endTimeOfDay <= $availableUntil;
    }

    /**
     * Get available time slots for a given date.
     *
     * @return array<array{start: Carbon, end: Carbon}>
     */
    public function getAvailableSlots(Carbon $date, int $slotDurationMinutes = 60): array
    {
        $slots = [];

        // Determine day boundaries
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        // Apply operating hours if set
        if ($this->available_from && $this->available_until) {
            $dayStart = $date->copy()->setTimeFromTimeString(
                Carbon::parse($this->available_from)->format('H:i:s')
            );
            $dayEnd = $date->copy()->setTimeFromTimeString(
                Carbon::parse($this->available_until)->format('H:i:s')
            );
        }

        // Get existing bookings for the day
        $existingBookings = $this->bookings()
            ->whereIn('status', array_map(
                fn (BookingStatus $status) => $status->value,
                BookingStatus::blockingStatuses()
            ))
            ->whereDate('start_time', $date)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        $currentTime = $dayStart->copy();

        foreach ($existingBookings as $booking) {
            $bookingStart = Carbon::parse($booking->start_time);
            $bookingEnd = Carbon::parse($booking->end_time)->addMinutes($this->buffer_minutes ?? 0);

            // Add available slots before this booking
            while ($currentTime->copy()->addMinutes($slotDurationMinutes) <= $bookingStart) {
                $slots[] = [
                    'start' => $currentTime->copy(),
                    'end' => $currentTime->copy()->addMinutes($slotDurationMinutes),
                ];
                $currentTime->addMinutes($slotDurationMinutes);
            }

            // Skip to after this booking (including buffer)
            $currentTime = $bookingEnd->copy();
        }

        // Add remaining slots until end of day
        while ($currentTime->copy()->addMinutes($slotDurationMinutes) <= $dayEnd) {
            $slots[] = [
                'start' => $currentTime->copy(),
                'end' => $currentTime->copy()->addMinutes($slotDurationMinutes),
            ];
            $currentTime->addMinutes($slotDurationMinutes);
        }

        return $slots;
    }

    // ========================================
    // PRICING LOGIC
    // ========================================

    /**
     * Get the base rate for a given price unit.
     */
    public function getRateForUnit(PriceUnit $unit): ?float
    {
        return match ($unit) {
            PriceUnit::HOUR => $this->hourly_rate ? (float) $this->hourly_rate : null,
            PriceUnit::DAY => $this->daily_rate ? (float) $this->daily_rate : null,
            PriceUnit::MONTH => $this->monthly_rate ? (float) $this->monthly_rate : null,
            default => null,
        };
    }

    /**
     * Get pricing rule for a specific plan.
     *
     * @return array{discount_percent?: int, free_hours_monthly?: int}|null
     */
    public function getPricingRuleForPlan(int $planId): ?array
    {
        if (!$this->pricing_rules) {
            return null;
        }

        foreach ($this->pricing_rules as $rule) {
            if (isset($rule['plan_id']) && $rule['plan_id'] === $planId) {
                return $rule;
            }
        }

        return null;
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to only active resources.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by resource type.
     */
    public function scopeOfType(Builder $query, ResourceType $type): Builder
    {
        return $query->where('resource_type', $type->value);
    }

    /**
     * Scope to filter resources available at a given time.
     */
    public function scopeAvailableAt(Builder $query, Carbon $startTime, Carbon $endTime): Builder
    {
        $blockingStatuses = array_map(
            fn (BookingStatus $status) => $status->value,
            BookingStatus::blockingStatuses()
        );

        return $query->whereDoesntHave('bookings', function (Builder $q) use ($startTime, $endTime, $blockingStatuses) {
            $q->whereIn('status', $blockingStatuses)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime);
        });
    }

    /**
     * Scope to filter by minimum capacity.
     */
    public function scopeWithCapacity(Builder $query, int $minCapacity): Builder
    {
        return $query->where('capacity', '>=', $minCapacity);
    }

    /**
     * Scope to filter by amenities.
     *
     * @param array<int> $amenityIds
     */
    public function scopeWithAmenities(Builder $query, array $amenityIds): Builder
    {
        return $query->whereHas('amenities', function (Builder $q) use ($amenityIds) {
            $q->whereIn('resource_amenities.id', $amenityIds);
        }, '>=', count($amenityIds));
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Get default price unit based on resource type.
     */
    public function getDefaultPriceUnit(): PriceUnit
    {
        return $this->resource_type->defaultPriceUnit();
    }

    /**
     * Get formatted price string.
     */
    public function getFormattedPrice(PriceUnit $unit): string
    {
        $rate = $this->getRateForUnit($unit);

        if ($rate === null) {
            return 'N/A';
        }

        return number_format($rate, 2) . ' ' . $this->currency . '/' . $unit->label();
    }
}
