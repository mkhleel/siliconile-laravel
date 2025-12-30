<?php

namespace Modules\Billing\Filament\Resources\Orders\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Billing\Enums\OrderStatus;
use Modules\Billing\Filament\Resources\Orders\OrderResource;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('mark_as_completed')
                ->action(fn () => $this->record->setStatus(OrderStatus::COMPLETED))
                ->visible(fn () => $this->record->status === OrderStatus::PENDING)
                ->color('success')
                ->icon('heroicon-s-check-circle'),
            Action::make('mark_as_cancelled')
                ->action(fn () => $this->record->cancel('Cancelled by admin'))
                ->visible(fn () => $this->record->status === OrderStatus::PENDING)
                ->color('danger')
                ->icon('heroicon-s-x-circle')
                ->requiresConfirmation(),
        ];
    }
}
