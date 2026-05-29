<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
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
            ->subject('Order Confirmation - '.$this->order->confirmation_number)
            ->greeting('Hello '.$this->order->name.'!')
            ->line('Thank you for your order.')
            ->line('Order ID: '.$this->order->id)
            ->line('Confirmation Number: '.$this->order->confirmation_number)
            ->line('Total Amount: $'.number_format($this->order->grand_total, 2))
            ->action('View Order', url('/orders/'.$this->order->id))
            ->line('We will notify you when your order ships.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'confirmation_number' => $this->order->confirmation_number,
        ];
    }
}
