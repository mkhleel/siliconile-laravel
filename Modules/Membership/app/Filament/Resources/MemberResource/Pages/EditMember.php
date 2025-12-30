<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\MemberResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Membership\Filament\Resources\MemberResource;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
