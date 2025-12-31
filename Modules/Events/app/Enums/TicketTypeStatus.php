<?php

declare(strict_types=1);

namespace Modules\Events\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Ticket Type Status Enum
 *
 * Defines the availability states of a ticket type.
 */
enum TicketTypeStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Paused = 'paused';
    case SoldOut = 'sold_out';
    case Expired = 'expired';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Paused => __('Paused'),
            self::SoldOut => __('Sold Out'),
            self::Expired => __('Expired'),
            self::Hidden => __('Hidden'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Paused => 'warning',
            self::SoldOut => 'danger',
            self::Expired => 'gray',
            self::Hidden => 'info',
        };
    }

    /**
     * Get all values as options array for forms.
     *
     * @return array<string, string>
     */
    public static function toOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }

    /**
     * Whether this ticket type is available for purchase.
     */
    public function isAvailable(): bool
    {
        return $this === self::Active;
    }
}
