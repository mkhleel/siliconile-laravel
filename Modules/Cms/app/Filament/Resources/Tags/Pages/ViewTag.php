<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Tags\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Cms\Filament\Resources\Tags\TagResource;

class ViewTag extends ViewRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
