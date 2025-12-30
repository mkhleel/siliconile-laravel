<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\PlanResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Membership\Filament\Resources\PlanResource;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;
}
