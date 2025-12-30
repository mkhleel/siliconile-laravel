<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\PlanResource\Pages;

use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Membership\Filament\Resources\PlanResource;

class ViewPlan extends ViewRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Overview')
                    ->schema([
                        Components\TextEntry::make('name')
                            ->label('Name'),
                        Components\TextEntry::make('slug')
                            ->label('Slug'),
                        Components\TextEntry::make('type')
                            ->label('Type')
                            ->badge(),
                        Components\TextEntry::make('price')
                            ->label('Price')
                            ->money(fn ($record) => $record->currency),
                        Components\TextEntry::make('duration_days')
                            ->label('Duration (days)'),
                        Components\TextEntry::make('currency')
                            ->label('Currency'),
                        Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        Components\IconEntry::make('is_featured')
                            ->label('Featured')
                            ->boolean(),
                        Components\TextEntry::make('sort_order')
                            ->label('Sort'),
                    ])
                    ->columns(3),

                Section::make('Features')
                    ->schema([
                        Components\IconEntry::make('wifi_access')
                            ->label('WiFi')
                            ->boolean(),
                        Components\IconEntry::make('meeting_room_access')
                            ->label('Meeting Room')
                            ->boolean(),
                        Components\TextEntry::make('meeting_hours_included')
                            ->label('Meeting Hours'),
                        Components\IconEntry::make('private_desk')
                            ->label('Private Desk')
                            ->boolean(),
                        Components\IconEntry::make('locker_access')
                            ->label('Locker Access')
                            ->boolean(),
                        Components\TextEntry::make('guest_passes')
                            ->label('Guest Passes'),
                    ])
                    ->columns(3),

                Section::make('Capacity')
                    ->schema([
                        Components\TextEntry::make('current_members')
                            ->label('Current Members'),
                        Components\TextEntry::make('max_members')
                            ->label('Max Members')
                            ->placeholder('Unlimited'),
                    ])
                    ->columns(2),

                Section::make('Description')
                    ->schema([
                        Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
