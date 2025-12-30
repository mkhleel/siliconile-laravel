<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\MemberResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Membership\Filament\Resources\MemberResource;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
