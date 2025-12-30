<?php

namespace Modules\Billing\Filament\Resources\Orders\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Billing\Filament\Resources\Orders\OrderResource;
use Modules\Billing\Models\Order;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate an order number if one wasn't provided
        if (! isset($data['order_number']) || empty($data['order_number'])) {
            $data['order_number'] = Order::generateOrderNumber();
        }

        return $data;
    }
}
