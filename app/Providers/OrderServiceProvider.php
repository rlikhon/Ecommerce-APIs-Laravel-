<?php

namespace App\Providers;

use App\Events\OrderConfirmed;
use App\Http\Contracts\OrderServiceInterface;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\SendOrderNotificationToAdmin;
use App\Services\OrderService;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
    }

    public function boot(): void
    {
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        $this->app['events']->listen(
            OrderConfirmed::class,
            SendOrderConfirmationEmail::class,
        );

        $this->app['events']->listen(
            OrderConfirmed::class,
            SendOrderNotificationToAdmin::class,
        );
    }
}
