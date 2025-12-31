<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\ApplicationResource\Pages;

use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Filament\Resources\ApplicationResource;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Application Overview')
                ->schema([
                    Components\TextEntry::make('application_code')
                        ->label('Code')
                        ->copyable(),
                    Components\TextEntry::make('startup_name'),
                    Components\TextEntry::make('cohort.name')
                        ->label('Cohort'),
                    Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (ApplicationStatus $state): string => $state->getColor() ?? 'gray'),
                    Components\TextEntry::make('email'),
                    Components\TextEntry::make('phone'),
                    Components\TextEntry::make('created_at')
                        ->label('Applied')
                        ->dateTime(),
                ])
                ->columns(4),

            Section::make('Business Information')
                ->schema([
                    Components\TextEntry::make('problem_statement')
                        ->columnSpanFull(),
                    Components\TextEntry::make('solution')
                        ->columnSpanFull(),
                    Components\TextEntry::make('industry'),
                    Components\TextEntry::make('business_model'),
                    Components\TextEntry::make('stage')
                        ->badge(),
                    Components\TextEntry::make('traction')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('Evaluation')
                ->schema([
                    Components\TextEntry::make('score')
                        ->formatStateUsing(fn (?float $state) => $state ? number_format($state, 1).'/100' : 'Not scored'),
                    Components\TextEntry::make('internal_notes')
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }
}
