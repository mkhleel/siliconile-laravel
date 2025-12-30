<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\SpaceResourceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\SpaceBooking\Filament\Resources\SpaceResourceResource;

class ListSpaceResources extends ListRecords
{
    protected static string $resource = SpaceResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
