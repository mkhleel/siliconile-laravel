<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\BookingResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Modules\SpaceBooking\Enums\BookingStatus;
use Modules\SpaceBooking\Enums\PaymentStatus;
use Modules\SpaceBooking\Enums\ResourceType;
use Modules\SpaceBooking\Models\Booking;
use Modules\SpaceBooking\Services\BookingService;

/**
 * Table configuration for Booking listing.
 */
class BookingTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Eager load relationships to prevent N+1 queries
            ->modifyQueryUsing(fn ($query) => $query->with(['resource', 'bookable']))
            ->columns([
                Columns\TextColumn::make('booking_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Booking code copied')
                    ->fontFamily('mono')
                    ->size('sm'),

                Columns\TextColumn::make('resource.name')
                    ->label('Resource')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('resource.resource_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ResourceType $state): string => $state->label())
                    ->color(fn (ResourceType $state): string => $state->color()),

                Columns\TextColumn::make('bookable.name')
                    ->label('Booked By')
                    ->searchable()
                    ->getStateUsing(fn (Booking $record): string => $record->getBookerName()),

                Columns\TextColumn::make('start_time')
                    ->label('Start')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),

                Columns\TextColumn::make('end_time')
                    ->label('End')
                    ->dateTime('H:i')
                    ->sortable(),

                Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(fn (Booking $record): string => $record->getDurationFormatted()),

                Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('SDG')
                    ->sortable(),

                Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus $state): string => $state->label())
                    ->color(fn (BookingStatus $state): string => $state->color())
                    ->sortable(),

                Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => $state->color())
                    ->toggleable(),

                Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->options(BookingStatus::options()),

                Filters\SelectFilter::make('payment_status')
                    ->options(PaymentStatus::options()),

                Filters\SelectFilter::make('space_resource_id')
                    ->label('Resource')
                    ->relationship('resource', 'name')
                    ->searchable()
                    ->preload(),

                Filters\Filter::make('start_time')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('start_time', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('start_time', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // Quick status actions
                Action::make('confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record): bool => $record->status->canConfirm())
                    ->action(function (Booking $record) {
                        app(BookingService::class)->confirmBooking($record);
                        Notification::make()
                            ->title('Booking Confirmed')
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record): bool => $record->status->canCancel())
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required(),
                    ])
                    ->action(function (Booking $record, array $data) {
                        app(BookingService::class)->cancelBooking($record, $data['reason']);
                        Notification::make()
                            ->title('Booking Cancelled')
                            ->warning()
                            ->send();
                    }),

                Action::make('checkIn')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('info')
                    ->visible(fn (Booking $record): bool => $record->status === BookingStatus::CONFIRMED && !$record->checked_in_at)
                    ->action(function (Booking $record) {
                        $record->checkIn();
                        Notification::make()
                            ->title('Checked In')
                            ->success()
                            ->send();
                    }),

                Action::make('checkOut')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('gray')
                    ->visible(fn (Booking $record): bool => $record->checked_in_at && !$record->checked_out_at)
                    ->action(function (Booking $record) {
                        $record->checkOut();
                        Notification::make()
                            ->title('Checked Out')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_time', 'desc')
            ->deferFilters();
    }
}
