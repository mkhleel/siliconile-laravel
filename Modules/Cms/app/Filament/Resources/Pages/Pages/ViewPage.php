<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Pages\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Cms\Filament\Resources\Pages\PageResource;

class ViewPage extends ViewRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
