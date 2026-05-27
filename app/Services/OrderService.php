<?php

namespace App\Services;

use App\Exceptions\Wishlist\ProductNotFoundException;
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

    public function createOrder(OrderDTO $dto): Order
    {
        // DB Transaction guarantees atomic compliance: all database saves succeed, or all fail together.
        try {
            return DB::transaction(function () use ($dto) {
                return DB::transaction(function () use ($dto) {
                    $order = Order::create([
                        'name' => $dto->name,
                        'email' => $dto->email,
                        'address' => $dto->address,
                        'mobile' => $dto->mobile,
                        'state' => $dto->state,
                        'zip' => $dto->zip,
                        'city' => $dto->city,
                        'grand_total' => $dto->grand_total,
                        'sub_total' => $dto->sub_total,
                        'discount' => $dto->discount,
                        'shipping' => $dto->shipping,
                        'payment_method' => $dto->payment_method,
                        'payment_status' => $dto->payment_status,
                        'status' => $dto->status,
                        'user_id' => $dto->user_id,
                    ]);

                    foreach ($dto->cart as $item) {
                        $order->items()->create([
                            'name' => $item['title'],
                            'product_id' => $item['product_id'],
                            'quantity' => $item['qty'],
                            'unit_price' => $item['price'],
                            'price' => $item['qty'] * $item['price'],
                            'size' => $item['size'] ?? null,
                        ]);
                    }

                    return $order;
                });
            });
        } catch (\Throwable $e) {
            throw new OrderCreationFailedException($e->getMessage());
        }
        
    }
}