<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\ApplicationResource\Pages;

use BackedEnum;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Filament\Resources\ApplicationResource;
use Modules\Incubation\Models\Application;
use Modules\Incubation\Services\ApplicationService;

/**
 * Kanban board view for managing applications through the selection pipeline.
 *
 * This is the primary interface for daily incubator operations.
 * Custom implementation compatible with Filament v4.
 */
class ApplicationsKanban extends Page
{
    protected static string $resource = ApplicationResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected string $view = 'incubation::filament.pages.applications-kanban';

    protected static ?string $title = 'Application Pipeline';

    /**
     * Get applications grouped by status for the Kanban board.
     *
     * @return array<string, array<int, Application>>
     */
    public function getApplicationsByStatus(): array
    {
        $applications = Application::query()
            ->with(['cohort'])
            ->whereIn('status', array_map(fn ($case) => $case->value, ApplicationStatus::kanbanCases()))
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = [];
        foreach (ApplicationStatus::kanbanCases() as $status) {
            $grouped[$status->value] = $applications->filter(
                fn (Application $app) => $app->status === $status
            )->values()->all();
        }

        return $grouped;
    }

    /**
     * Get all status columns for the Kanban board.
     *
     * @return array<int, array{value: string, label: string, color: string}>
     */
    public function getStatuses(): array
    {
        return array_map(fn (ApplicationStatus $status) => [
            'value' => $status->value,
            'label' => $status->getLabel(),
            'color' => $status->getColor(),
        ], ApplicationStatus::kanbanCases());
    }

    /**
     * Handle drag-and-drop status change.
     */
    #[On('application-moved')]
    public function moveApplication(int $applicationId, string $newStatus): void
    {
        $application = Application::find($applicationId);
        $targetStatus = ApplicationStatus::from($newStatus);

        if (! $application) {
            Notification::make()
                ->danger()
                ->title('Application not found')
                ->send();

            return;
        }

        // Validate the transition
        if (! $application->canTransitionTo($targetStatus)) {
            Notification::make()
                ->danger()
                ->title('Invalid Transition')
                ->body("Cannot move from {$application->status->getLabel()} to {$targetStatus->getLabel()}")
                ->send();

            return;
        }

        $service = app(ApplicationService::class);

        try {
            match ($targetStatus) {
                ApplicationStatus::SCREENING => $service->moveToScreening($application, auth()->id()),
                ApplicationStatus::ACCEPTED => $service->accept($application, auth()->id()),
                ApplicationStatus::REJECTED => $service->reject($application, auth()->id()),
                default => $application->updateStatus($targetStatus, auth()->id()),
            };

            Notification::make()
                ->success()
                ->title('Status Updated')
                ->body("{$application->startup_name} moved to {$targetStatus->getLabel()}")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('list')
                ->label('Table View')
                ->icon('heroicon-o-table-cells')
                ->url(ApplicationResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
