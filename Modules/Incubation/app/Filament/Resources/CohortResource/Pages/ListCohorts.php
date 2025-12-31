<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\CohortResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Incubation\Filament\Resources\CohortResource;

class ListCohorts extends ListRecords
{
    protected static string $resource = CohortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
