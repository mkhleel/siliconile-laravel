<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Categories\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Cms\Filament\Resources\Categories\CategoryResource;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
