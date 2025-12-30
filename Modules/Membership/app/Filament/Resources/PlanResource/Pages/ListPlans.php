<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\PlanResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Membership\Filament\Resources\PlanResource;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;
}
