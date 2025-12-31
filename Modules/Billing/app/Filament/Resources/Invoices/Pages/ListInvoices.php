<?php

declare(strict_types=1);

namespace Modules\Billing\Filament\Resources\Invoices\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Billing\Filament\Resources\Invoices\InvoiceResource;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
