<?php

namespace Modules\Billing\Filament\Resources\Orders\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Billing\Enums\OrderStatus;
use Modules\Billing\Filament\Resources\Orders\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            ActionGroup::make([
                Action::make('mark_as_processing')
                    ->label('Mark as Processing')
                    ->icon(Heroicon::ArrowPath)
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('Mark Order as Processing'))
                    ->modalDescription(__('This will mark the order as being prepared.'))
                    ->form([
                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder(__('Optional: Add a note about this status change'))
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $description = $data['description'] ?? __('Order is being prepared');
                        $this->record->setStatus(OrderStatus::PROCESSING, $description);

                        Notification::make()
                            ->success()
                            ->title(__('Order status updated'))
                            ->body(__('Order status updated to Processing'))
                            ->send();
                    })
                    ->visible(fn () => $this->record->status === OrderStatus::PENDING),

                Action::make('mark_as_shipped')
                    ->label('Mark as Shipped')
                    ->icon(Heroicon::Truck)
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading(__('Mark Order as Shipped'))
                    ->modalDescription(__('Confirm that this order has been handed to the shipping company.'))
                    ->form([
                        TextInput::make('tracking_number')
                            ->label('Tracking Number')
                            ->placeholder(__('Enter tracking number'))
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Shipping Details')
                            ->placeholder(__('Add carrier name, estimated delivery date, or other shipping details'))
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $trackingNumber = $data['tracking_number'];

                        // Update tracking number on the order
                        $this->record->tracking_number = $trackingNumber;
                        $this->record->save();

                        // Create description with tracking info
                        $description = $data['description']
                            ? __('Tracking Number: :tracking', ['tracking' => $trackingNumber])."\n".$data['description']
                            : __('Tracking Number: :tracking', ['tracking' => $trackingNumber]);

                        $this->record->setStatus(OrderStatus::SHIPPED, $description);

                        // todo:: notify user


                        Notification::make()
                            ->success()
                            ->title(__('Order marked as Shipped'))
                            ->body(__('Tracking Number: :tracking', ['tracking' => $trackingNumber]))
                            ->send();
                    })
                    ->visible(fn () => $this->record->status === OrderStatus::PROCESSING),

                Action::make('mark_as_out_for_delivery')
                    ->label('Out for Delivery')
                    ->icon(Heroicon::MapPin)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('Mark as Out for Delivery'))
                    ->modalDescription(__('Confirm that this order is currently out for delivery.'))
                    ->form([
                        Textarea::make('description')
                            ->label('Delivery Notes')
                            ->placeholder(__('Optional: Add delivery details or estimated time'))
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $description = $data['description'] ?? __('Order is out for delivery');
                        $this->record->setStatus(OrderStatus::OUT_FOR_DELIVERY, $description);

                        Notification::make()
                            ->success()
                            ->title(__('Order is now Out for Delivery'))
                            ->send();
                    })
                    ->visible(fn () => $this->record->status === OrderStatus::SHIPPED),

                Action::make('mark_as_delivered')
                    ->label('Mark as Delivered')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('Confirm Delivery'))
                    ->modalDescription(__('Confirm that this order has been successfully delivered to the customer.'))
                    ->form([
                        Textarea::make('description')
                            ->label('Delivery Confirmation')
                            ->placeholder(__('Optional: Add delivery confirmation details'))
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $description = $data['description'] ?? __('Order has been delivered successfully');
                        $this->record->setStatus(OrderStatus::DELIVERED, $description);

                        Notification::make()
                            ->success()
                            ->title(__('Order marked as Delivered'))
                            ->send();
                    })
                    ->visible(fn () => $this->record->status === OrderStatus::OUT_FOR_DELIVERY),

                Action::make('mark_as_completed')
                    ->label('Mark as Completed')
                    ->icon(Heroicon::CheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('Complete Order'))
                    ->modalDescription(__('This will finalize the order as completed.'))
                    ->form([
                        Textarea::make('description')
                            ->label('Completion Notes')
                            ->placeholder(__('Optional: Add final notes'))
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $description = $data['description'] ?? __('Order is complete');
                        $this->record->setStatus(OrderStatus::COMPLETED, $description);

                        Notification::make()
                            ->success()
                            ->title(__('Order marked as Completed'))
                            ->send();
                    })
                    ->visible(fn () => in_array($this->record->status, [OrderStatus::PENDING, OrderStatus::DELIVERED])),

                Action::make('mark_as_cancelled')
                    ->label('Cancel Order')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('Cancel Order'))
                    ->modalDescription(__('Are you sure you want to cancel this order? This action cannot be undone.'))
                    ->form([
                        Textarea::make('description')
                            ->label('Cancellation Reason')
                            ->placeholder(__('Please provide a reason for cancelling this order'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $this->record->setStatus(OrderStatus::CANCELLED, $data['description']);

                        Notification::make()
                            ->success()
                            ->title(__('Order has been cancelled'))
                            ->send();
                    })
                    ->visible(fn () => ! in_array($this->record->status, [OrderStatus::COMPLETED, OrderStatus::CANCELLED, OrderStatus::REFUNDED])),

                Action::make('mark_as_refunded')
                    ->label('Mark as Refunded')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__('Refund Order'))
                    ->modalDescription(__('Mark this order as refunded.'))
                    ->form([
                        Textarea::make('description')
                            ->label('Refund Details')
                            ->placeholder(__('Add refund reason, amount, and transaction details'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $this->record->setStatus(OrderStatus::REFUNDED, $data['description']);

                        Notification::make()
                            ->success()
                            ->title(__('Order marked as Refunded'))
                            ->send();
                    })
                    ->visible(fn () => in_array($this->record->status, [OrderStatus::COMPLETED, OrderStatus::DELIVERED, OrderStatus::CANCELLED])),
            ])
                ->label('Change Status')
                ->icon(Heroicon::Cog6Tooth)
                ->color('gray')
                ->button(),
        ];
    }
}
