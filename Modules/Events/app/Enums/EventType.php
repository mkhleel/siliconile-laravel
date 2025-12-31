<?php

declare(strict_types=1);

namespace Modules\Events\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Event Types Enum
 *
 * Defines the different types of events supported by the system.
 */
enum EventType: string implements HasColor, HasIcon, HasLabel
{
    case Workshop = 'workshop';
    case Course = 'course';
    case Meetup = 'meetup';
    case Conference = 'conference';
    case Webinar = 'webinar';

    public function getLabel(): string
    {
        return match ($this) {
            self::Workshop => __('Workshop'),
            self::Course => __('Course'),
            self::Meetup => __('Meetup'),
            self::Conference => __('Conference'),
            self::Webinar => __('Webinar'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Workshop => 'gray',
            self::Course => 'success',
            self::Meetup => 'info',
            self::Conference => 'warning',
            self::Webinar => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Workshop => 'heroicon-o-wrench-screwdriver',
            self::Course => 'heroicon-o-academic-cap',
            self::Meetup => 'heroicon-o-user-group',
            self::Conference => 'heroicon-o-microphone',
            self::Webinar => 'heroicon-o-video-camera',
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
     * Whether this event type typically has multiple sessions.
     */
    public function isMultiSession(): bool
    {
        return match ($this) {
            self::Course, self::Conference => true,
            default => false,
        };
    }
}
