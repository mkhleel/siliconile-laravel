<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Incubation\Filament\Resources\ApplicationResource;
use Modules\Incubation\Filament\Resources\CohortResource;
use Modules\Incubation\Filament\Resources\MentorResource;

class IncubationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'incubation';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                CohortResource::class,
                ApplicationResource::class,
                MentorResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Boot logic if needed
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
