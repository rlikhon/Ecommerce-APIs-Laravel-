<?php

namespace App\Exceptions\Order;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InvalidOrderDataException extends Exception
{
    public function __construct(
        string $message = 'Invalid order data provided',
        private array $errors = []
    ) {
        parent::__construct($message);
    }

    public function report(): void
    {
        Log::warning("Invalid order data: {$this->message}", [
            'errors' => $this->errors,
        ]);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'INVALID_ORDER_DATA',
            'message' => $this->message,
            'details' => $this->errors,
        ], 422);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
