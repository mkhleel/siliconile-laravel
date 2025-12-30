<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Enums;

enum PriceUnit: string
{
    case HOUR = 'hour';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::HOUR => 'Hour',
            self::DAY => 'Day',
            self::WEEK => 'Week',
            self::MONTH => 'Month',
        };
    }

    /**
     * Get plural label.
     */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::HOUR => 'Hours',
            self::DAY => 'Days',
            self::WEEK => 'Weeks',
            self::MONTH => 'Months',
        };
    }

    /**
     * Convert duration in minutes to this unit.
     */
    public function fromMinutes(int $minutes): float
    {
        return match ($this) {
            self::HOUR => $minutes / 60,
            self::DAY => $minutes / 1440, // 24 * 60
            self::WEEK => $minutes / 10080, // 7 * 24 * 60
            self::MONTH => $minutes / 43200, // 30 * 24 * 60 (approximate)
        };
    }

    /**
     * Convert this unit to minutes.
     */
    public function toMinutes(float $quantity): int
    {
        return (int) match ($this) {
            self::HOUR => $quantity * 60,
            self::DAY => $quantity * 1440,
            self::WEEK => $quantity * 10080,
            self::MONTH => $quantity * 43200,
        };
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
