<?php

declare(strict_types=1);

namespace Modules\Incubation\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Application status enum representing the selection pipeline stages.
 *
 * Flow: submitted -> screening -> interview_scheduled -> interviewed -> accepted/rejected
 */
enum ApplicationStatus: string implements HasColor, HasIcon, HasLabel
{
    case SUBMITTED = 'submitted';
    case SCREENING = 'screening';
    case INTERVIEW_SCHEDULED = 'interview_scheduled';
    case INTERVIEWED = 'interviewed';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SUBMITTED => 'Submitted',
            self::SCREENING => 'Screening',
            self::INTERVIEW_SCHEDULED => 'Interview Scheduled',
            self::INTERVIEWED => 'Interviewed',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::WITHDRAWN => 'Withdrawn',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SUBMITTED => 'gray',
            self::SCREENING => 'info',
            self::INTERVIEW_SCHEDULED => 'warning',
            self::INTERVIEWED => 'primary',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
            self::WITHDRAWN => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SUBMITTED => 'heroicon-o-inbox',
            self::SCREENING => 'heroicon-o-eye',
            self::INTERVIEW_SCHEDULED => 'heroicon-o-calendar',
            self::INTERVIEWED => 'heroicon-o-chat-bubble-left-right',
            self::ACCEPTED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
            self::WITHDRAWN => 'heroicon-o-arrow-uturn-left',
        };
    }

    /**
     * Get the title for Kanban board display.
     */
    public function getTitle(): string
    {
        return $this->getLabel() ?? $this->value;
    }

    /**
     * Define which statuses appear on the Kanban board.
     *
     * @return array<self>
     */
    public static function kanbanCases(): array
    {
        return [
            self::SUBMITTED,
            self::SCREENING,
            self::INTERVIEW_SCHEDULED,
            self::INTERVIEWED,
            self::ACCEPTED,
            self::REJECTED,
        ];
    }

    /**
     * Check if a transition to the given status is allowed.
     */
    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::SUBMITTED => in_array($status, [self::SCREENING, self::REJECTED, self::WITHDRAWN]),
            self::SCREENING => in_array($status, [self::INTERVIEW_SCHEDULED, self::REJECTED, self::WITHDRAWN]),
            self::INTERVIEW_SCHEDULED => in_array($status, [self::INTERVIEWED, self::SCREENING, self::REJECTED, self::WITHDRAWN]),
            self::INTERVIEWED => in_array($status, [self::ACCEPTED, self::REJECTED, self::SCREENING]),
            self::ACCEPTED => false, // Cannot change once accepted
            self::REJECTED => in_array($status, [self::SCREENING]), // Can move back for reconsideration
            self::WITHDRAWN => false,
        };
    }

    /**
     * Get all allowed transitions from this status.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::SUBMITTED => [self::SCREENING, self::REJECTED, self::WITHDRAWN],
            self::SCREENING => [self::INTERVIEW_SCHEDULED, self::REJECTED, self::WITHDRAWN],
            self::INTERVIEW_SCHEDULED => [self::INTERVIEWED, self::SCREENING, self::REJECTED, self::WITHDRAWN],
            self::INTERVIEWED => [self::ACCEPTED, self::REJECTED, self::SCREENING],
            self::ACCEPTED => [],
            self::REJECTED => [self::SCREENING],
            self::WITHDRAWN => [],
        };
    }

    /**
     * Check if this status is considered "active" in the pipeline.
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::SUBMITTED,
            self::SCREENING,
            self::INTERVIEW_SCHEDULED,
            self::INTERVIEWED,
        ]);
    }

    /**
     * Check if this status is a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::ACCEPTED, self::REJECTED, self::WITHDRAWN]);
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
