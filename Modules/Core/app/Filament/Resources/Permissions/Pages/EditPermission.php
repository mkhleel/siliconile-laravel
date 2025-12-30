<?php

namespace Modules\Core\Filament\Resources\Permissions\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Core\Filament\Resources\Permissions\PermissionResource;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;
}
