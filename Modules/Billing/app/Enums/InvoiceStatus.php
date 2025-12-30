<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Invoice Status Enum - Represents the lifecycle states of an invoice.
 *
 * Lifecycle: Draft → Sent → Paid/Overdue → Void
 */
enum InvoiceStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case VOID = 'void';
    case PARTIALLY_PAID = 'partially_paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::SENT => __('Sent'),
            self::PAID => __('Paid'),
            self::OVERDUE => __('Overdue'),
            self::VOID => __('Void'),
            self::PARTIALLY_PAID => __('Partially Paid'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::PAID => 'success',
            self::OVERDUE => 'danger',
            self::VOID => 'gray',
            self::PARTIALLY_PAID => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-pencil-square',
            self::SENT => 'heroicon-o-paper-airplane',
            self::PAID => 'heroicon-o-check-circle',
            self::OVERDUE => 'heroicon-o-exclamation-triangle',
            self::VOID => 'heroicon-o-x-circle',
            self::PARTIALLY_PAID => 'heroicon-o-clock',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT => __('Invoice is being prepared and not yet sent to customer'),
            self::SENT => __('Invoice has been sent and is awaiting payment'),
            self::PAID => __('Invoice has been fully paid'),
            self::OVERDUE => __('Invoice payment is past due date'),
            self::VOID => __('Invoice has been cancelled/voided'),
            self::PARTIALLY_PAID => __('Invoice has been partially paid'),
        };
    }

    /**
     * Check if invoice can be edited.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if invoice is finalized (has number).
     */
    public function isFinalized(): bool
    {
        return in_array($this, [self::SENT, self::PAID, self::OVERDUE, self::PARTIALLY_PAID]);
    }

    /**
     * Check if invoice can be paid.
     */
    public function canBePaid(): bool
    {
        return in_array($this, [self::SENT, self::OVERDUE, self::PARTIALLY_PAID]);
    }

    /**
     * Check if invoice can be voided.
     */
    public function canBeVoided(): bool
    {
        return in_array($this, [self::DRAFT, self::SENT, self::OVERDUE]);
    }

    /**
     * Get allowed transitions from current status.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SENT, self::VOID],
            self::SENT => [self::PAID, self::OVERDUE, self::PARTIALLY_PAID, self::VOID],
            self::OVERDUE => [self::PAID, self::PARTIALLY_PAID, self::VOID],
            self::PARTIALLY_PAID => [self::PAID, self::OVERDUE, self::VOID],
            self::PAID, self::VOID => [],
        };
    }
}
