<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\ResourceAmenityResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\SpaceBooking\Filament\Resources\ResourceAmenityResource;

class ListResourceAmenities extends ListRecords
{
    protected static string $resource = ResourceAmenityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
