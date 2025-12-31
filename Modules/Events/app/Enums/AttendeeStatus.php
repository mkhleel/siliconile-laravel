<?php

declare(strict_types=1);

namespace Modules\Events\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Attendee Status Enum
 *
 * Defines the lifecycle states of an event attendee/ticket.
 */
enum AttendeeStatus: string implements HasColor, HasIcon, HasLabel
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Waitlisted = 'waitlisted';
    case NoShow = 'no_show';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => __('Pending Payment'),
            self::Confirmed => __('Confirmed'),
            self::CheckedIn => __('Checked In'),
            self::Cancelled => __('Cancelled'),
            self::Expired => __('Expired'),
            self::Waitlisted => __('Waitlisted'),
            self::NoShow => __('No Show'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Confirmed => 'success',
            self::CheckedIn => 'info',
            self::Cancelled => 'danger',
            self::Expired => 'gray',
            self::Waitlisted => 'purple',
            self::NoShow => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PendingPayment => 'heroicon-o-clock',
            self::Confirmed => 'heroicon-o-check-circle',
            self::CheckedIn => 'heroicon-o-qr-code',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Expired => 'heroicon-o-exclamation-triangle',
            self::Waitlisted => 'heroicon-o-queue-list',
            self::NoShow => 'heroicon-o-user-minus',
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
     * Whether the attendee can check in with this status.
     */
    public function canCheckIn(): bool
    {
        return $this === self::Confirmed;
    }

    /**
     * Whether the ticket is considered valid/active.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Confirmed, self::CheckedIn], true);
    }

    /**
     * Whether the ticket takes up capacity.
     */
    public function occupiesCapacity(): bool
    {
        return in_array($this, [
            self::PendingPayment,
            self::Confirmed,
            self::CheckedIn,
            self::Waitlisted,
        ], true);
    }
}
