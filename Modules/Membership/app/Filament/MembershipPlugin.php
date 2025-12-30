<?php

namespace Modules\Membership\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class MembershipPlugin implements Plugin
{
    public function getId(): string
    {
        return 'membership';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverPages(__DIR__.'/Pages', 'Modules\\Membership\\Filament\\Pages');
        $panel->discoverResources(__DIR__.'/Resources', 'Modules\\Membership\\Filament\\Resources');
    }

    public function boot(Panel $panel): void {}
}
