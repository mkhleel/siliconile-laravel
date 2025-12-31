<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\CohortResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Incubation\Filament\Resources\CohortResource;

class CreateCohort extends CreateRecord
{
    protected static string $resource = CohortResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
