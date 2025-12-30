<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\BookingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\SpaceBooking\Filament\Resources\BookingResource;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Calendar View')
                ->icon('heroicon-o-calendar-days')
                ->url(fn () => BookingResource::getUrl('calendar'))
                ->color('info'),

            Actions\CreateAction::make(),
        ];
    }
}
