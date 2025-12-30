<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\SpaceBooking\Filament\Resources\BookingResource;
use Modules\SpaceBooking\Filament\Resources\ResourceAmenityResource;
use Modules\SpaceBooking\Filament\Resources\SpaceResourceResource;

class SpaceBookingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'spacebooking';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                SpaceResourceResource::class,
                BookingResource::class,
                ResourceAmenityResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
