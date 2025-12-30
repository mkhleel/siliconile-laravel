<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
            self::NO_SHOW => 'No Show',
        };
    }

    /**
     * Get color for UI badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::COMPLETED => 'gray',
            self::NO_SHOW => 'danger',
        };
    }

    /**
     * Check if this status blocks the time slot (prevents overlapping bookings).
     */
    public function blocksTimeSlot(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            self::CANCELLED, self::COMPLETED, self::NO_SHOW => false,
        };
    }

    /**
     * Get statuses that should be considered when checking availability.
     *
     * @return array<self>
     */
    public static function blockingStatuses(): array
    {
        return [self::PENDING, self::CONFIRMED];
    }

    /**
     * Check if booking can be cancelled from this status.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED => true,
            default => false,
        };
    }

    /**
     * Check if booking can be confirmed from this status.
     */
    public function canConfirm(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if booking can be marked as completed from this status.
     */
    public function canComplete(): bool
    {
        return $this === self::CONFIRMED;
    }

    /**
     * Get all cases as options for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $case) => $case->label(), self::cases())
        );
    }
}
