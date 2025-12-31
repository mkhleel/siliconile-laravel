<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\CohortResource\Pages;

use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Incubation\Enums\CohortStatus;
use Modules\Incubation\Filament\Resources\CohortResource;
use Modules\Incubation\Services\CohortService;

class ViewCohort extends ViewRecord
{
    protected static string $resource = CohortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('open_applications')
                ->label('Open Applications')
                ->icon('heroicon-o-megaphone')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === CohortStatus::DRAFT)
                ->action(function (): void {
                    app(CohortService::class)->openForApplications($this->record);
                    $this->refreshFormData(['status']);
                }),
            Actions\Action::make('activate')
                ->label('Activate Program')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === CohortStatus::REVIEWING)
                ->action(function (): void {
                    app(CohortService::class)->activate($this->record);
                    $this->refreshFormData(['status']);
                }),
            Actions\Action::make('duplicate')
                ->label('Duplicate Cohort')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('New Cohort Name')
                        ->required()
                        ->default(fn () => $this->record->name.' (Copy)'),
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Program Start Date')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('Program End Date')
                        ->required()
                        ->afterOrEqual('start_date'),
                ])
                ->action(function (array $data): void {
                    app(CohortService::class)->duplicateCohort($this->record, $data['name'], [
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                    ]);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cohort Overview')
                ->schema([
                    Components\TextEntry::make('name'),
                    Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (CohortStatus $state): string => $state->getColor() ?? 'gray'),
                    Components\TextEntry::make('description')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Timeline')
                ->schema([
                    Components\TextEntry::make('application_start_date')
                        ->label('Applications Open')
                        ->date(),
                    Components\TextEntry::make('application_end_date')
                        ->label('Applications Close')
                        ->date(),
                    Components\TextEntry::make('start_date')
                        ->label('Program Starts')
                        ->date(),
                    Components\TextEntry::make('end_date')
                        ->label('Program Ends')
                        ->date(),
                ])
                ->columns(4),

            Section::make('Capacity & Statistics')
                ->schema([
                    Components\TextEntry::make('capacity')
                        ->label('Max Startups'),
                    Components\TextEntry::make('accepted_count')
                        ->label('Accepted'),
                    Components\TextEntry::make('available_spots')
                        ->label('Available Spots'),
                    Components\TextEntry::make('applications_count')
                        ->label('Total Applications')
                        ->state(fn ($record) => $record->applications()->count()),
                ])
                ->columns(4),
        ]);
    }
}
