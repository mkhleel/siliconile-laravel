<?php

declare(strict_types=1);

namespace Modules\Incubation\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Cohort status enum representing the lifecycle of a program cycle.
 */
enum CohortStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case OPEN_FOR_APPLICATIONS = 'open_for_applications';
    case REVIEWING = 'reviewing';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ARCHIVED = 'archived';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::OPEN_FOR_APPLICATIONS => 'Open for Applications',
            self::REVIEWING => 'Reviewing Applications',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::ARCHIVED => 'Archived',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::OPEN_FOR_APPLICATIONS => 'success',
            self::REVIEWING => 'warning',
            self::ACTIVE => 'primary',
            self::COMPLETED => 'info',
            self::ARCHIVED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-pencil-square',
            self::OPEN_FOR_APPLICATIONS => 'heroicon-o-megaphone',
            self::REVIEWING => 'heroicon-o-clipboard-document-check',
            self::ACTIVE => 'heroicon-o-play',
            self::COMPLETED => 'heroicon-o-check-badge',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    /**
     * Check if applications can be submitted in this status.
     */
    public function acceptsApplications(): bool
    {
        return $this === self::OPEN_FOR_APPLICATIONS;
    }

    /**
     * Check if this status is visible to the public.
     */
    public function isPublic(): bool
    {
        return in_array($this, [
            self::OPEN_FOR_APPLICATIONS,
            self::ACTIVE,
            self::COMPLETED,
        ]);
    }

    /**
     * Check if a transition to the given status is allowed.
     */
    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::DRAFT => in_array($status, [self::OPEN_FOR_APPLICATIONS]),
            self::OPEN_FOR_APPLICATIONS => in_array($status, [self::REVIEWING, self::DRAFT]),
            self::REVIEWING => in_array($status, [self::ACTIVE, self::OPEN_FOR_APPLICATIONS]),
            self::ACTIVE => in_array($status, [self::COMPLETED]),
            self::COMPLETED => in_array($status, [self::ARCHIVED]),
            self::ARCHIVED => false,
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
