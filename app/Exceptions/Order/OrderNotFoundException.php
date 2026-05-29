<?php

namespace App\Exceptions\Order;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OrderNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Order not found',
        private ?string $orderId = null
    ) {
        parent::__construct($message);
    }

    public function report(): void
    {
        $id = $this->orderId ?? 'unknown';
        Log::warning("Order not found: {$id}");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'ORDER_NOT_FOUND',
            'message' => $this->message,
            'order_id' => $this->orderId,
        ], 404);
    }
}
