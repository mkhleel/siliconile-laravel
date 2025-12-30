<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\PlanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Membership\Filament\Resources\PlanResource;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
