<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\ApplicationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Incubation\Filament\Resources\ApplicationResource;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
