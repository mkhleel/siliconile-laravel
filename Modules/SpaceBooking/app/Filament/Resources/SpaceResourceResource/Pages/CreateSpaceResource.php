<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\SpaceResourceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\SpaceBooking\Filament\Resources\SpaceResourceResource;

class CreateSpaceResource extends CreateRecord
{
    protected static string $resource = SpaceResourceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
