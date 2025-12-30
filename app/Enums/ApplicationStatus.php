<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ApplicationStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::UNDER_REVIEW => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::UNDER_REVIEW => 'heroicon-o-eye',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::UNDER_REVIEW, self::REJECTED]),
            self::UNDER_REVIEW => in_array($status, [self::APPROVED, self::REJECTED, self::PENDING]),
            self::APPROVED => false, // Cannot change once approved
            self::REJECTED => in_array($status, [self::PENDING]), // Can reapply
        };
    }
}
