<?php

namespace Modules\Core\Filament\Resources\Admins\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Core\Filament\Resources\Admins\AdminResource;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;
}
