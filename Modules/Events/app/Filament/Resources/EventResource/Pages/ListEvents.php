<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources\EventResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Events\Filament\Resources\EventResource;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
