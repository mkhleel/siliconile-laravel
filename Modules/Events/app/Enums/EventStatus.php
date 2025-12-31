<?php

declare(strict_types=1);

namespace Modules\Events\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Event Status Enum
 *
 * Defines the lifecycle states of an event.
 */
enum EventStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Postponed = 'postponed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => __('Published'),
            self::Cancelled => __('Cancelled'),
            self::Completed => __('Completed'),
            self::Postponed => __('Postponed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Cancelled => 'danger',
            self::Completed => 'info',
            self::Postponed => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Published => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Completed => 'heroicon-o-check-badge',
            self::Postponed => 'heroicon-o-clock',
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
     * Whether registrations are allowed in this status.
     */
    public function allowsRegistration(): bool
    {
        return $this === self::Published;
    }

    /**
     * Whether the event is visible to the public.
     */
    public function isPubliclyVisible(): bool
    {
        return in_array($this, [self::Published, self::Completed], true);
    }
}
