<?php

namespace Modules\Cms\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CmsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'cms';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverPages(__DIR__.'/Pages', 'Modules\\Cms\\Filament\\Pages');
        $panel->discoverResources(__DIR__.'/Resources', 'Modules\\Cms\\Filament\\Resources');
    }

    public function boot(Panel $panel): void {}
}
