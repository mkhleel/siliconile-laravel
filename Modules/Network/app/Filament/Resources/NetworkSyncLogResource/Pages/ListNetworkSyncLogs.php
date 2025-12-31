<?php

declare(strict_types=1);

namespace Modules\Network\Filament\Resources\NetworkSyncLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Network\Filament\Resources\NetworkSyncLogResource;

class ListNetworkSyncLogs extends ListRecords
{
    protected static string $resource = NetworkSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
