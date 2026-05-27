<?php

namespace App\Exceptions\Order;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OrderCreationFailedException extends Exception
{
    public function report(): void
    {
        Log::error("Order processing failed: {$this->getMessage()}");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'We could not process your order at this time.',
            'error' => $this->getMessage()
        ], 422);
    }
}
