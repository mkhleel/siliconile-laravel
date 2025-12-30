<?php

namespace Modules\Core\Filament\Resources\Permissions\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Core\Filament\Resources\Permissions\PermissionResource;

class ViewPermission extends ViewRecord
{
    protected static string $resource = PermissionResource::class;

    public function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
