<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Pages\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;
}
