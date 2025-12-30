<?php

declare(strict_types=1);

namespace Modules\Billing\Filament\Resources\Invoices\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Billing\Filament\Resources\Invoices\InvoiceResource;
use Modules\Billing\Services\InvoiceService;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set initial totals to 0 - will be calculated after items are saved
        $data['subtotal'] = 0;
        $data['tax_amount'] = 0;
        $data['total'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Recalculate totals after items are saved
        $this->record->calculateTotals();
        $this->record->save();

        Notification::make()
            ->title('Invoice Created')
            ->body("Draft invoice created. Add items and finalize when ready.")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
