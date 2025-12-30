<?php

declare(strict_types=1);

namespace Modules\Membership\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MemberType: string implements HasLabel, HasColor, HasIcon
{
    case INDIVIDUAL = 'individual';
    case CORPORATE = 'corporate';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::CORPORATE => 'Corporate',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INDIVIDUAL => 'info',
            self::CORPORATE => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::INDIVIDUAL => 'heroicon-o-user',
            self::CORPORATE => 'heroicon-o-building-office-2',
        };
    }
}
