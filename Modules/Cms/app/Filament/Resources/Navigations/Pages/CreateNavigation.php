<?php

namespace Modules\Cms\Filament\Resources\Navigations\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Modules\Cms\Filament\Resources\Navigations\NavigationResource;

class CreateNavigation extends CreateRecord
{
    use Translatable;

    protected static string $resource = NavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),

        ];
    }
}
