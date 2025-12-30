<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case REFUNDED = 'refunded';
    case PARTIAL = 'partial';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PAID => 'Paid',
            self::REFUNDED => 'Refunded',
            self::PARTIAL => 'Partially Paid',
        };
    }

    /**
     * Get color for UI badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::UNPAID => 'danger',
            self::PAID => 'success',
            self::REFUNDED => 'warning',
            self::PARTIAL => 'info',
        };
    }

    /**
     * Check if payment allows booking to proceed.
     */
    public function allowsBooking(): bool
    {
        return match ($this) {
            self::PAID, self::PARTIAL => true,
            default => false,
        };
    }

    /**
     * Get all cases as options for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $case) => $case->label(), self::cases())
        );
    }
}
