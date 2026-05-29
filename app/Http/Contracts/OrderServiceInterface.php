<?php

namespace App\Http\Contracts;

use App\DataTransferObjects\OrderDTO;
use App\Models\Order;
use App\Models\User;

interface OrderServiceInterface
{
    public function getUserOrders(User $user, int $page = 1, int $perPage = 15): array;

    public function createOrder(OrderDTO $dto): array;

    public function confirmOrder(Order $order, ?string $description = null): Order;
}
