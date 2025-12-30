<?php

namespace Modules\Core\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\SpatieLaravelTranslatablePlugin;

class CorePlugin implements Plugin
{
    public function getId(): string
    {
        return 'core';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\\Core\\Filament\\Resources'
        );
        $panel->discoverPages(
            in: __DIR__.'/Pages',
            for: 'Modules\\Core\\Filament\\Pages'
        );

        // Get config('core.localization.languages') after set using ConfigSync container run
        // Also can use $panel->when(ConfigSync::class, function (Panel $panel) {...
        //        $panel->bootUsing(function (Panel $panel) {
        //            $panel->plugins([
        //                SpatieLaravelTranslatablePlugin::make()->defaultLocales(config('core.localization.languages', [])),
        //            ]);
        //        });
    }

    public function boot(Panel $panel): void {}
}
