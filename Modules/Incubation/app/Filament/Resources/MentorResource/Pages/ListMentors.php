<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\MentorResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Incubation\Filament\Resources\MentorResource;

class ListMentors extends ListRecords
{
    protected static string $resource = MentorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
