<?php

namespace Modules\Billing\Observers;

use Modules\Billing\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Create initial status history when order is created
        if ($order->status) {
            $order->statusHistories()->create([
                'status' => $order->status,
                'description' => $order->status->getDescription(),
            ]);
        }
    }
}
