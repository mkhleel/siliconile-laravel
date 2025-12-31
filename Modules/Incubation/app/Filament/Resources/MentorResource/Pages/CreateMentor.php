<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\MentorResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Incubation\Filament\Resources\MentorResource;

class CreateMentor extends CreateRecord
{
    protected static string $resource = MentorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
