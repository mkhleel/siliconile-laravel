<?php

namespace Modules\Core\Filament\Resources\Admins\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Core\Filament\Resources\Admins\AdminResource;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;
}
