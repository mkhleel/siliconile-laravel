<?php

declare(strict_types=1);

namespace Modules\Events\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Location Type Enum
 *
 * Defines the venue type for events.
 */
enum LocationType: string implements HasLabel
{
    case Physical = 'physical';
    case Virtual = 'virtual';
    case Hybrid = 'hybrid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Physical => __('In-Person'),
            self::Virtual => __('Online'),
            self::Hybrid => __('Hybrid (In-Person + Online)'),
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
     * Whether a physical address is required.
     */
    public function requiresPhysicalAddress(): bool
    {
        return in_array($this, [self::Physical, self::Hybrid], true);
    }

    /**
     * Whether a virtual link is required.
     */
    public function requiresVirtualLink(): bool
    {
        return in_array($this, [self::Virtual, self::Hybrid], true);
    }
}
