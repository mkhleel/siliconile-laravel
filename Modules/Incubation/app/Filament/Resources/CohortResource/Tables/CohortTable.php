<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\CohortResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Modules\Incubation\Enums\CohortStatus;
use Modules\Incubation\Models\Cohort;
use Modules\Incubation\Services\CohortService;

/**
 * Table configuration for Cohort listing.
 */
class CohortTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('name')
                    ->label('Cohort Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Cohort $record): string => $record->slug),

                Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (CohortStatus $state): string => $state->getLabel() ?? $state->value)
                    ->color(fn (CohortStatus $state): string => $state->getColor() ?? 'gray')
                    ->sortable(),

                Columns\TextColumn::make('application_dates')
                    ->label('Applications')
                    ->getStateUsing(function (Cohort $record): string {
                        if (! $record->application_start_date) {
                            return 'Not set';
                        }

                        $start = $record->application_start_date->format('M j');
                        $end = $record->application_end_date?->format('M j, Y') ?? 'Open';

                        return "{$start} - {$end}";
                    }),

                Columns\TextColumn::make('program_dates')
                    ->label('Program Period')
                    ->getStateUsing(function (Cohort $record): string {
                        return $record->start_date->format('M j').' - '.$record->end_date->format('M j, Y');
                    }),

                Columns\TextColumn::make('capacity_status')
                    ->label('Capacity')
                    ->getStateUsing(fn (Cohort $record): string => "{$record->accepted_count}/{$record->capacity}")
                    ->badge()
                    ->color(fn (Cohort $record): string => $record->hasCapacity() ? 'success' : 'danger'),

                Columns\TextColumn::make('applications_count')
                    ->label('Applications')
                    ->counts('applications')
                    ->sortable(),

                Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->options(CohortStatus::options())
                    ->multiple(),

                Filters\Filter::make('accepting_applications')
                    ->label('Currently Accepting')
                    ->query(fn ($query) => $query->acceptingApplications()),

                Filters\Filter::make('active')
                    ->label('Active Programs')
                    ->query(fn ($query) => $query->active()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                \Filament\Actions\Action::make('open_applications')
                    ->label('Open Applications')
                    ->icon('heroicon-o-megaphone')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to open this cohort for applications?')
                    ->visible(fn (Cohort $record): bool => $record->status === CohortStatus::DRAFT)
                    ->action(function (Cohort $record): void {
                        app(CohortService::class)->openForApplications($record);
                        Notification::make()
                            ->success()
                            ->title('Cohort opened for applications')
                            ->send();
                    }),
                \Filament\Actions\Action::make('close_applications')
                    ->label('Close Applications')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Cohort $record): bool => $record->status === CohortStatus::OPEN_FOR_APPLICATIONS)
                    ->action(function (Cohort $record): void {
                        app(CohortService::class)->closeApplications($record);
                        Notification::make()
                            ->success()
                            ->title('Applications closed')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
