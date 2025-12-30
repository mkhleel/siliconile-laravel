<?php

namespace Modules\Billing\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Billing\Models\Order;

class OrderPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
        //        public $invoice
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Order Paid: :number', ['number' => $this->order->order_number]))
            ->greeting(__('Hello :name', ['name' => $notifiable->name]))
            ->line(__('Thank you for your payment. Your order has been confirmed.'))
            ->line(__('Order Number: :number', ['number' => $this->order->order_number]))
            ->line(__('Total Amount: :amount :currency', [
                'amount' => $this->order->total,
                'currency' => $this->order->currency,
            ]))
            ->action(__('View Order'), url('/orders/'.$this->order->id))
//            ->attachData($this->invoice, 'invoice.pdf', [
//                'mime' => 'application/pdf',
//            ])
            ->line(__('Thank you for using :app!', ['app' => config('app.name')]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
        ];
    }
}
