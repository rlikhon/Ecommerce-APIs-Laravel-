<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Notifications\AdminOrderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendOrderNotificationToAdmin
{
    public function handle(OrderConfirmed $event): void
    {
        try {
            $adminEmails = config('app.admin_notification_emails', []);

            if (empty($adminEmails)) {
                Log::warning('No admin notification emails configured');

                return;
            }

            foreach ($adminEmails as $email) {
                Notification::route('mail', $email)
                    ->notify(new AdminOrderNotification($event->order));
            }

            Log::info('Order notification sent to admins', ['order_id' => $event->order->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
