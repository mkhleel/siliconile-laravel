<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\ApplicationResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Enums\StartupStage;
use Modules\Incubation\Models\Application;
use Modules\Incubation\Services\ApplicationService;

/**
 * Table configuration for Application listing.
 */
class ApplicationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Eager load relationships to prevent N+1 queries
            ->modifyQueryUsing(fn ($query) => $query->with(['cohort', 'user']))
            ->columns([
                Columns\TextColumn::make('application_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Code copied')
                    ->fontFamily('mono')
                    ->size('sm'),

                Columns\TextColumn::make('startup_name')
                    ->label('Startup')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Application $record): string => $record->email),

                Columns\TextColumn::make('cohort.name')
                    ->label('Cohort')
                    ->sortable()
                    ->toggleable(),

                Columns\TextColumn::make('primary_founder_name')
                    ->label('Lead Founder')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereRaw("JSON_EXTRACT(founders_data, '$[0].name') LIKE ?", ["%{$search}%"]);
                    }),

                Columns\TextColumn::make('stage')
                    ->badge()
                    ->formatStateUsing(fn (?StartupStage $state): string => $state?->getLabel() ?? '-')
                    ->color(fn (?StartupStage $state): string => $state?->getColor() ?? 'gray')
                    ->toggleable(),

                Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ApplicationStatus $state): string => $state->getLabel() ?? $state->value)
                    ->color(fn (ApplicationStatus $state): string => $state->getColor() ?? 'gray')
                    ->sortable(),

                Columns\TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1) : '-')
                    ->sortable()
                    ->toggleable(),

                Columns\TextColumn::make('interview_scheduled_at')
                    ->label('Interview')
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Filters\SelectFilter::make('cohort_id')
                    ->label('Cohort')
                    ->relationship('cohort', 'name')
                    ->searchable()
                    ->preload(),

                Filters\SelectFilter::make('status')
                    ->options(ApplicationStatus::options())
                    ->multiple(),

                Filters\SelectFilter::make('stage')
                    ->options(StartupStage::options()),

                Filters\Filter::make('has_score')
                    ->label('Scored')
                    ->query(fn ($query) => $query->whereNotNull('score')),

                Filters\Filter::make('interview_scheduled')
                    ->label('Interview Scheduled')
                    ->query(fn ($query) => $query->whereNotNull('interview_scheduled_at')),

                Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Applied From'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Applied Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                // Quick status actions
                \Filament\Actions\Action::make('move_to_screening')
                    ->label('Screen')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (Application $record): bool => $record->status === ApplicationStatus::SUBMITTED)
                    ->action(function (Application $record): void {
                        app(ApplicationService::class)->moveToScreening($record, auth()->id());
                        Notification::make()->success()->title('Moved to Screening')->send();
                    }),

                \Filament\Actions\Action::make('schedule_interview')
                    ->label('Schedule Interview')
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->visible(fn (Application $record): bool => $record->status === ApplicationStatus::SCREENING)
                    ->form([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Date & Time')
                            ->required()
                            ->native(false)
                            ->minDate(now()),
                        Forms\Components\TextInput::make('meeting_link')
                            ->label('Meeting Link')
                            ->url(),
                    ])
                    ->action(function (Application $record, array $data): void {
                        app(ApplicationService::class)->scheduleInterview(
                            $record,
                            $data['scheduled_at'],
                            'Online',
                            $data['meeting_link'],
                            auth()->id()
                        );
                        Notification::make()->success()->title('Interview Scheduled')->send();
                    }),

                \Filament\Actions\Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Accept this application and enroll the startup in the program?')
                    ->visible(fn (Application $record): bool => $record->status === ApplicationStatus::INTERVIEWED)
                    ->action(function (Application $record): void {
                        app(ApplicationService::class)->accept($record, auth()->id());
                        Notification::make()->success()->title('Application Accepted')->send();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->rows(3),
                    ])
                    ->visible(fn (Application $record): bool => $record->isInPipeline())
                    ->action(function (Application $record, array $data): void {
                        app(ApplicationService::class)->reject($record, auth()->id(), $data['reason'] ?? null);
                        Notification::make()->success()->title('Application Rejected')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('bulk_move_to_screening')
                        ->label('Move to Screening')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $service = app(ApplicationService::class);
                            foreach ($records as $record) {
                                if ($record->status === ApplicationStatus::SUBMITTED) {
                                    $service->moveToScreening($record, auth()->id());
                                }
                            }
                            Notification::make()->success()->title('Applications moved to screening')->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
