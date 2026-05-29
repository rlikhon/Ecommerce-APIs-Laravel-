<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessOrderConfirmationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        try {
            Notification::route('mail', $this->order->email)
                ->notify(new OrderConfirmationNotification($this->order));

            Log::info('Order confirmation email sent', ['order_id' => $this->order->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Exception $exception): void
    {
        Log::error('Order confirmation email job failed after retries', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
