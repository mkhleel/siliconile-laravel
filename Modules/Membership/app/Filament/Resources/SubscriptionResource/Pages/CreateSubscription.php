<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\SubscriptionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Membership\Filament\Resources\SubscriptionResource;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;
}
