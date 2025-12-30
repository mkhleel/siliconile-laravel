<?php

namespace Modules\Core\Filament\Resources\Permissions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Core\Filament\Resources\Permissions\PermissionResource;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;
}
