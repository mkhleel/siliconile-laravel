<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources\EventResource\Pages;

use Filament\Actions;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Events\Enums\EventStatus;
use Modules\Events\Filament\Resources\EventResource;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('publish')
                ->label('Publish')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === EventStatus::Draft)
                ->action(function (): void {
                    $this->record->update(['status' => EventStatus::Published]);
                    Notification::make()
                        ->title('Event Published')
                        ->success()
                        ->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('check_in')
                ->label('Check-In Mode')
                ->icon(Heroicon::OutlinedQrCode)
                ->color('info')
                ->url(fn () => route('filament.admin.pages.event-check-in', ['event' => $this->record->id])),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Event Overview')
                            ->columnSpan(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('type')
                                    ->badge(),

                                TextEntry::make('status')
                                    ->badge(),

                                TextEntry::make('short_description')
                                    ->columnSpanFull(),

                                TextEntry::make('description')
                                    ->html()
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Quick Stats')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('attendees_count')
                                    ->label('Total Attendees')
                                    ->state(fn ($record) => $record->attendees()->count())
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('confirmed_count')
                                    ->label('Confirmed')
                                    ->state(fn ($record) => $record->attendees()->confirmed()->count())
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('checked_in_count')
                                    ->label('Checked In')
                                    ->state(fn ($record) => $record->attendees()->checkedIn()->count())
                                    ->badge()
                                    ->color('warning'),

                                TextEntry::make('total_capacity')
                                    ->label('Capacity')
                                    ->placeholder('Unlimited'),

                                TextEntry::make('ticket_types_count')
                                    ->label('Ticket Types')
                                    ->state(fn ($record) => $record->ticketTypes()->count()),
                            ]),

                        Section::make('Schedule & Location')
                            ->columnSpan(2)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('start_date')
                                            ->dateTime('l, F j, Y \a\t g:i A'),

                                        TextEntry::make('end_date')
                                            ->dateTime('l, F j, Y \a\t g:i A')
                                            ->placeholder('Same day'),

                                        TextEntry::make('timezone'),

                                        TextEntry::make('location_type')
                                            ->badge(),

                                        TextEntry::make('location_name')
                                            ->label('Venue'),

                                        TextEntry::make('location_address')
                                            ->label('Address'),

                                        TextEntry::make('location_link')
                                            ->label('Online Link')
                                            ->url(fn ($state) => $state)
                                            ->openUrlInNewTab(),
                                    ]),
                            ]),

                        Section::make('Media')
                            ->columnSpan(1)
                            ->schema([
                                ImageEntry::make('banner_image')
                                    ->label('Banner'),

                                ImageEntry::make('thumbnail_image')
                                    ->label('Thumbnail'),
                            ]),
                    ]),
            ]);
    }
}
