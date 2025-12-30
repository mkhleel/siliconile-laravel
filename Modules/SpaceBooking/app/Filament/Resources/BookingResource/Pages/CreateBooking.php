<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\BookingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\SpaceBooking\Filament\Resources\BookingResource;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
