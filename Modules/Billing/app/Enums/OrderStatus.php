<?php

namespace Modules\Billing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::PROCESSING => __('Processing'),
            self::SHIPPED => __('Shipped'),
            self::OUT_FOR_DELIVERY => __('Out for Delivery'),
            self::DELIVERED => __('Delivered'),
            self::COMPLETED => __('Completed'),
            self::CANCELLED => __('Cancelled'),
            self::REFUNDED => __('Refunded'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::SHIPPED => 'primary',
            self::OUT_FOR_DELIVERY => 'success',
            self::DELIVERED => 'success',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'secondary',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PENDING => __('Order is awaiting payment'),
            self::PROCESSING => __('Order is being prepared'),
            self::SHIPPED => __('Order has been handed to shipping company'),
            self::OUT_FOR_DELIVERY => __('Order is out for delivery'),
            self::DELIVERED => __('Order has been delivered'),
            self::COMPLETED => __('Order is complete'),
            self::CANCELLED => __('Order has been cancelled'),
            self::REFUNDED => __('Order has been refunded'),
        };
    }
}
