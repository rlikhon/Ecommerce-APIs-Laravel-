<?php

namespace App\Contracts;

use App\Models\Order;
use App\DataTransferObjects\OrderDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface OrderServiceInterface
{
    public function getUserOrders(User $user): Collection;
    
    public function createOrder(OrderDTO $dto): Order;
}
