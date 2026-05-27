<?php

namespace App\Services;

use App\Exceptions\Wishlist\ProductNotFoundException;
use App\Exceptions\Wishlist\WishlistItemNotFoundException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    /**
     * Handle the business logic for creating an order.
     */
    public function getUserOrders(User $user): Collection
    {
        return Order::where('user_id', $user->id)
            ->with('orderItems')
            ->latest()
            ->get();
    }
}