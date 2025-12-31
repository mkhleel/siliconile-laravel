<?php

declare(strict_types=1);

namespace Modules\Events\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * EventsPlugin
 *
 * Registers Events module resources with Filament admin panel.
 */
class EventsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'events';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Modules\\Events\\Filament\\Resources'
        );

        $panel->discoverPages(
            in: __DIR__.'/Pages',
            for: 'Modules\\Events\\Filament\\Pages'
        );

        $panel->discoverWidgets(
            in: __DIR__.'/Widgets',
            for: 'Modules\\Events\\Filament\\Widgets'
        );
    }

    public function boot(Panel $panel): void {}
}
