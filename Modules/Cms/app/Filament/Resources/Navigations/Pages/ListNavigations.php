<?php

namespace Modules\Cms\Filament\Resources\Navigations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Modules\Cms\Filament\Resources\Navigations\NavigationResource;

class ListNavigations extends ListRecords
{
    use Translatable;

    protected static string $resource = NavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
