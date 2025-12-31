<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\ApplicationResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Filament\Resources\ApplicationResource;
use Modules\Incubation\Services\ApplicationService;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            // Pipeline Actions
            Actions\Action::make('move_to_screening')
                ->label('Move to Screening')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn () => $this->record->status === ApplicationStatus::SUBMITTED)
                ->action(function (): void {
                    app(ApplicationService::class)->moveToScreening($this->record, auth()->id());
                    $this->record->refresh();
                    Notification::make()->success()->title('Moved to Screening')->send();
                }),

            Actions\Action::make('schedule_interview')
                ->label('Schedule Interview')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->visible(fn () => $this->record->status === ApplicationStatus::SCREENING)
                ->form([
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Date & Time')
                        ->required()
                        ->native(false)
                        ->minDate(now()),
                    Forms\Components\Select::make('location')
                        ->options(['Online' => 'Online', 'Office' => 'Office'])
                        ->default('Online'),
                    Forms\Components\TextInput::make('meeting_link')
                        ->label('Meeting Link')
                        ->url(),
                ])
                ->action(function (array $data): void {
                    app(ApplicationService::class)->scheduleInterview(
                        $this->record,
                        $data['scheduled_at'],
                        $data['location'],
                        $data['meeting_link'] ?? null,
                        auth()->id()
                    );
                    $this->record->refresh();
                    Notification::make()->success()->title('Interview Scheduled')->send();
                }),

            Actions\Action::make('complete_interview')
                ->label('Interview Completed')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->visible(fn () => $this->record->status === ApplicationStatus::INTERVIEW_SCHEDULED)
                ->form([
                    Forms\Components\Textarea::make('notes')
                        ->label('Interview Notes')
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    app(ApplicationService::class)->completeInterview(
                        $this->record,
                        $data['notes'] ?? null,
                        auth()->id()
                    );
                    $this->record->refresh();
                    Notification::make()->success()->title('Interview Marked Complete')->send();
                }),

            Actions\Action::make('accept')
                ->label('Accept Application')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Accept Application')
                ->modalDescription('This will enroll the startup in the program. Continue?')
                ->visible(fn () => $this->record->status === ApplicationStatus::INTERVIEWED)
                ->action(function (): void {
                    app(ApplicationService::class)->accept($this->record, auth()->id());
                    $this->record->refresh();
                    Notification::make()->success()->title('Application Accepted')->send();
                }),

            Actions\Action::make('reject')
                ->label('Reject Application')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isInPipeline())
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    app(ApplicationService::class)->reject($this->record, auth()->id(), $data['reason']);
                    $this->record->refresh();
                    Notification::make()->success()->title('Application Rejected')->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Recalculate score if evaluation scores changed
        if ($this->record->wasChanged('evaluation_scores')) {
            $scores = $this->record->evaluation_scores ?? [];
            if (! empty($scores)) {
                $averageScore = count($scores) > 0
                    ? array_sum($scores) / count($scores) * 10
                    : null;
                $this->record->update(['score' => $averageScore]);
            }
        }
    }
}
