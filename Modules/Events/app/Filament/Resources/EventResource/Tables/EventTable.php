<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources\EventResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Modules\Events\Enums\EventStatus;
use Modules\Events\Enums\EventType;
use Modules\Events\Models\Event;

/**
 * Table configuration for Event listing.
 */
class EventTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withCount(['attendees', 'ticketTypes'])
                ->with(['organizer'])
            )
            ->defaultSort('start_date', 'desc')
            ->columns([
                Columns\ImageColumn::make('thumbnail_image')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(url('/images/default-event.png')),

                Columns\TextColumn::make('title')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Event $record): string => $record->short_description ?? '')
                    ->wrap()
                    ->limit(50),

                Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                Columns\TextColumn::make('start_date')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->description(fn (Event $record): ?string => $record->end_date
                        ? 'to '.$record->end_date->format('M j, Y H:i')
                        : null
                    ),

                Columns\TextColumn::make('location_type')
                    ->label('Location')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? 'N/A'),

                Columns\TextColumn::make('attendees_count')
                    ->label('Attendees')
                    ->counts('attendees')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->suffix(fn (Event $record): string => $record->total_capacity
                        ? " / {$record->total_capacity}"
                        : ''
                    ),

                Columns\TextColumn::make('ticket_types_count')
                    ->label('Tickets')
                    ->counts('ticketTypes')
                    ->badge()
                    ->color('gray'),

                Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->toggleable(),

                Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->options(EventStatus::class),

                Filters\SelectFilter::make('type')
                    ->options(EventType::class),

                Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),

                Filters\TernaryFilter::make('is_free')
                    ->label('Free Event'),

                Filters\Filter::make('upcoming')
                    ->query(fn ($query) => $query->where('start_date', '>=', now()))
                    ->label('Upcoming Events')
                    ->toggle(),

                Filters\Filter::make('past')
                    ->query(fn ($query) => $query->where('start_date', '<', now()))
                    ->label('Past Events')
                    ->toggle(),

                Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('start_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('start_date', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('publish')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Event $record): bool => $record->status === EventStatus::Draft)
                    ->action(function (Event $record): void {
                        $record->update(['status' => EventStatus::Published]);
                        Notification::make()
                            ->title('Event Published')
                            ->body('The event is now live and visible to the public.')
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Event')
                    ->modalDescription('Are you sure you want to cancel this event? All attendees will be notified.')
                    ->visible(fn (Event $record): bool => in_array($record->status, [
                        EventStatus::Draft,
                        EventStatus::Published,
                    ]))
                    ->action(function (Event $record): void {
                        $record->update(['status' => EventStatus::Cancelled]);
                        // TODO: Dispatch event to notify attendees
                        Notification::make()
                            ->title('Event Cancelled')
                            ->warning()
                            ->send();
                    }),

                Action::make('duplicate')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplicate Event')
                    ->modalDescription('Create a copy of this event with all settings but new dates?')
                    ->action(function (Event $record): void {
                        $newEvent = $record->replicate([
                            'slug',
                            'registered_count',
                            'created_at',
                            'updated_at',
                        ]);
                        $newEvent->title = $record->title.' (Copy)';
                        $newEvent->slug = \Illuminate\Support\Str::slug($newEvent->title);
                        $newEvent->status = EventStatus::Draft;
                        $newEvent->save();

                        // Duplicate ticket types
                        foreach ($record->ticketTypes as $ticketType) {
                            $newTicket = $ticketType->replicate([
                                'quantity_sold',
                                'quantity_reserved',
                            ]);
                            $newTicket->event_id = $newEvent->id;
                            $newTicket->quantity_sold = 0;
                            $newTicket->quantity_reserved = 0;
                            $newTicket->save();
                        }

                        Notification::make()
                            ->title('Event Duplicated')
                            ->body('A draft copy has been created.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('publish_selected')
                        ->label('Publish Selected')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(
                            fn (Event $event) => $event->update(['status' => EventStatus::Published])
                        ))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Events Yet')
            ->emptyStateDescription('Create your first event to get started.')
            ->emptyStateIcon(Heroicon::OutlinedCalendar)
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Create Event'),
            ]);
    }
}
