<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Admin] New Order Received - '.$this->order->confirmation_number)
            ->line('A new order has been received!')
            ->line('Order ID: '.$this->order->id)
            ->line('Confirmation Number: '.$this->order->confirmation_number)
            ->line('Customer: '.$this->order->name.' ('.$this->order->email.')')
            ->line('Total Amount: $'.number_format($this->order->grand_total, 2))
            ->line('Items: '.count($this->order->orderItems))
            ->action('View Order', url('/admin/orders/'.$this->order->id))
            ->line('Please process this order as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'confirmation_number' => $this->order->confirmation_number,
        ];
    }
}
