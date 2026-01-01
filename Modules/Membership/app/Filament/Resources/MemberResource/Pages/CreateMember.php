<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\MemberResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Membership\Events\MemberCreated;
use Modules\Membership\Filament\Resources\MemberResource;
use Modules\Membership\Services\MembershipService;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate member code
        $membershipService = app(MembershipService::class);
        $data['member_code'] = $membershipService->generateMemberCode();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Fire event for new member creation
        event(new MemberCreated($this->record));
    }
}
