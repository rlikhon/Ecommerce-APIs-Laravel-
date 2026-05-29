<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Jobs\ProcessOrderConfirmationEmail;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmail
{
    public function handle(OrderConfirmed $event): void
    {
        try {
            ProcessOrderConfirmationEmail::dispatch($event->order)
                ->onQueue('emails');

            Log::info('Order confirmation email job queued', ['order_id' => $event->order->id]);
        } catch (\Exception $e) {
            Log::error('Failed to queue order confirmation email', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
