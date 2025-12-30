<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\ResourceAmenityResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\SpaceBooking\Filament\Resources\ResourceAmenityResource;

class CreateResourceAmenity extends CreateRecord
{
    protected static string $resource = ResourceAmenityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
