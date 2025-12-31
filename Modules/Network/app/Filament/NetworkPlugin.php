<?php

declare(strict_types=1);

namespace Modules\Network\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Network\Filament\Pages\NetworkSettings;
use Modules\Network\Filament\Resources\NetworkSyncLogResource;
use Modules\Network\Filament\Widgets\OnlineUsersWidget;

/**
 * Filament Plugin for Network Module.
 *
 * Registers all Filament components (pages, resources, widgets) for the module.
 */
class NetworkPlugin implements Plugin
{
    public function getId(): string
    {
        return 'network';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                NetworkSettings::class,
            ])
            ->resources([
                NetworkSyncLogResource::class,
            ])
            ->widgets([
                OnlineUsersWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Any boot-time initialization
    }

    public static function make(): static
    {
        return new static;
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
