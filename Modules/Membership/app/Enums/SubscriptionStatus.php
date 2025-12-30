<?php

declare(strict_types=1);

namespace Modules\Membership\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case EXPIRING = 'expiring';
    case GRACE_PERIOD = 'grace_period';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case SUSPENDED = 'suspended';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::EXPIRING => 'Expiring Soon',
            self::GRACE_PERIOD => 'Grace Period',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::ACTIVE => 'success',
            self::EXPIRING => 'warning',
            self::GRACE_PERIOD => 'orange',
            self::EXPIRED => 'danger',
            self::CANCELLED => 'gray',
            self::SUSPENDED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::ACTIVE => 'heroicon-o-check-badge',
            self::EXPIRING => 'heroicon-o-exclamation-triangle',
            self::GRACE_PERIOD => 'heroicon-o-clock',
            self::EXPIRED => 'heroicon-o-x-circle',
            self::CANCELLED => 'heroicon-o-no-symbol',
            self::SUSPENDED => 'heroicon-o-pause-circle',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE || $this === self::EXPIRING || $this === self::GRACE_PERIOD;
    }

    public function canRenew(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::EXPIRING,
            self::GRACE_PERIOD,
            self::EXPIRED,
        ], true);
    }
}
