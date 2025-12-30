<?php

declare(strict_types=1);

namespace Modules\Billing\Filament\Resources\Invoices\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Support\Icons\Heroicon;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Filament\Resources\Invoices\InvoiceResource;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === InvoiceStatus::DRAFT),
        ];
    }

    protected function beforeSave(): void
    {
        // Ensure only draft invoices can be edited
        if (!$this->record->isEditable()) {
            Notification::make()
                ->title('Cannot Edit')
                ->body('This invoice has been finalized and cannot be edited.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        // Recalculate totals after save
        $this->record->calculateTotals();
        $this->record->save();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
