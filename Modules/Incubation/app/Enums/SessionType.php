<?php

declare(strict_types=1);

namespace Modules\Incubation\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Session type enum for mentorship sessions.
 */
enum SessionType: string implements HasColor, HasLabel
{
    case ONE_ON_ONE = 'one_on_one';
    case GROUP = 'group';
    case WORKSHOP = 'workshop';
    case OFFICE_HOURS = 'office_hours';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ONE_ON_ONE => 'One-on-One',
            self::GROUP => 'Group Session',
            self::WORKSHOP => 'Workshop',
            self::OFFICE_HOURS => 'Office Hours',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ONE_ON_ONE => 'primary',
            self::GROUP => 'info',
            self::WORKSHOP => 'success',
            self::OFFICE_HOURS => 'warning',
        };
    }

    /**
     * Get options for select fields.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}
