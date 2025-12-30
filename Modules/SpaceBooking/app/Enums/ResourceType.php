<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Enums;

enum ResourceType: string
{
    case MEETING_ROOM = 'meeting_room';
    case HOT_DESK = 'hot_desk';
    case PRIVATE_OFFICE = 'private_office';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::MEETING_ROOM => 'Meeting Room',
            self::HOT_DESK => 'Hot Desk',
            self::PRIVATE_OFFICE => 'Private Office',
        };
    }

    /**
     * Get the default price unit for this resource type.
     */
    public function defaultPriceUnit(): PriceUnit
    {
        return match ($this) {
            self::MEETING_ROOM => PriceUnit::HOUR,
            self::HOT_DESK => PriceUnit::DAY,
            self::PRIVATE_OFFICE => PriceUnit::MONTH,
        };
    }

    /**
     * Get icon for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::MEETING_ROOM => 'heroicon-o-user-group',
            self::HOT_DESK => 'heroicon-o-computer-desktop',
            self::PRIVATE_OFFICE => 'heroicon-o-building-office',
        };
    }

    /**
     * Get color for badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::MEETING_ROOM => 'info',
            self::HOT_DESK => 'success',
            self::PRIVATE_OFFICE => 'warning',
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
