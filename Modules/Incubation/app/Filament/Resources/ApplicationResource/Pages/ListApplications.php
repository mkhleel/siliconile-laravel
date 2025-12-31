<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\ApplicationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Incubation\Filament\Resources\ApplicationResource;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('kanban')
                ->label('Kanban View')
                ->icon('heroicon-o-view-columns')
                ->url(ApplicationResource::getUrl('kanban'))
                ->color('gray'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
