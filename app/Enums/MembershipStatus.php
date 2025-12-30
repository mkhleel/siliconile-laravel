<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MembershipStatus: string implements HasLabel, HasColor, HasIcon
{
    case INACTIVE = 'inactive';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::EXPIRED => 'Expired',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INACTIVE => 'gray',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'warning',
            self::EXPIRED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::INACTIVE => 'heroicon-o-minus-circle',
            self::ACTIVE => 'heroicon-o-check-badge',
            self::SUSPENDED => 'heroicon-o-pause-circle',
            self::EXPIRED => 'heroicon-o-clock',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canAccessSpace(): bool
    {
        return in_array($this, [self::ACTIVE]);
    }
}
