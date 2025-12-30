<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\SpaceResourceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\SpaceBooking\Filament\Resources\SpaceResourceResource;

class EditSpaceResource extends EditRecord
{
    protected static string $resource = SpaceResourceResource::class;

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
