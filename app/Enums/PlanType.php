<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PlanType: string implements HasLabel
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::YEARLY => 'Yearly',
        };
    }

    public function durationInDays(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::YEARLY => 365,
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 2,
            self::MONTHLY => 3,
            self::QUARTERLY => 4,
            self::YEARLY => 5,
        };
    }
}
