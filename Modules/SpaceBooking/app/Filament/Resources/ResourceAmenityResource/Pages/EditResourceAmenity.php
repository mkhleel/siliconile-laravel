<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\ResourceAmenityResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\SpaceBooking\Filament\Resources\ResourceAmenityResource;

class EditResourceAmenity extends EditRecord
{
    protected static string $resource = ResourceAmenityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
