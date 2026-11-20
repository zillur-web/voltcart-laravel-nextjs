<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = str_replace('_', ' ', $this->order->status);

        return (new MailMessage)
            ->subject('Order '.$this->order->number.' is now '.$status)
            ->greeting('Hello '.$this->order->shipping_name.',')
            ->line('Your order '.$this->order->number.' is now '.$status.'.')
            ->line('Order total: $'.$this->order->total)
            ->line('If you did not place this order, contact SHOP.CO support.');
    }
}
