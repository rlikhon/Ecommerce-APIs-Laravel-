<?php

namespace App\Services;

use App\DataTransferObjects\OrderDTO;
use App\Enums\OrderStatus;
use App\Events\OrderConfirmed;
use App\Events\OrderCreated;
use App\Exceptions\Order\InvalidOrderDataException;
use App\Exceptions\Order\OrderCreationFailedException;
use App\Http\Contracts\OrderServiceInterface;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService implements OrderServiceInterface
{
    /**
     * Retrieve paginated orders for a user with eager loading.
     * Handles errors gracefully and returns structured response.
     */
    public function getUserOrders(User $user, int $page = 1, int $perPage = 15): array
    {
        try {
            $orders = Order::where('user_id', $user->id)
                ->with('orderItems')
                ->latest('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            return [
                'items' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user orders', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle the business logic for creating an order with idempotency.
     * Returns structured response with status and data.
     */
    public function createOrder(OrderDTO $dto): array
    {
        try {
            $order = DB::transaction(function () use ($dto) {
                $existingOrder = Order::where('confirmation_number', $dto->confirmation_number)
                    ->first();

                if ($existingOrder) {
                    Log::warning('Duplicate order attempt detected', [
                        'confirmation_number' => $dto->confirmation_number,
                        'user_id' => $dto->user_id,
                    ]);

                    return $existingOrder;
                }

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
                    'confirmation_number' => $dto->confirmation_number,
                ]);

                foreach ($dto->cart as $item) {
                    $order->orderItems()->create([
                        'name' => $item['title'],
                        'product_id' => $item['product_id'],
                        'quantity' => $item['qty'],
                        'unit_price' => $item['price'],
                        'price' => $item['qty'] * $item['price'],
                        'size' => $item['size'] ?? null,
                    ]);
                }

                $this->logOrderAction($order, 'created', null, OrderStatus::Pending, 'Order created via API');

                OrderCreated::dispatch($order);

                Log::info('Order created successfully', [
                    'order_id' => $order->id,
                    'confirmation_number' => $order->confirmation_number,
                    'user_id' => $order->user_id,
                ]);

                return $order;
            });

            return [
                'response' => [
                    'message' => 'Order placed successfully',
                    'order_id' => $order->id,
                    'confirmation_number' => $order->confirmation_number,
                    'order' => new OrderResource($order),
                ],
                'status' => 201,
            ];
        } catch (OrderCreationFailedException $e) {
            Log::error('Order creation failed', [
                'user_id' => $dto->user_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'response' => [
                    'error' => 'ORDER_CREATION_FAILED',
                    'message' => 'We could not process your order. Please try again.',
                ],
                'status' => 422,
            ];
        } catch (InvalidOrderDataException $e) {
            return [
                'response' => [
                    'error' => 'INVALID_ORDER_DATA',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors(),
                ],
                'status' => 422,
            ];
        } catch (\Throwable $e) {
            Log::error('Unexpected error during order creation', [
                'user_id' => $dto->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'response' => [
                    'error' => 'INTERNAL_SERVER_ERROR',
                    'message' => 'An unexpected error occurred. Please contact support.',
                ],
                'status' => 500,
            ];
        }
    }

    /**
     * Confirm an order and trigger confirmation event.
     */
    public function confirmOrder(Order $order, ?string $description = null): Order
    {
        if ($order->status->isTerminal()) {
            throw new InvalidOrderDataException('Cannot confirm an order that is already in a terminal state');
        }

        try {
            return DB::transaction(function () use ($order, $description) {
                $oldStatus = $order->status;

                $order->update([
                    'status' => OrderStatus::Confirmed->value,
                    'processed_at' => now(),
                ]);

                $this->logOrderAction(
                    $order,
                    'confirmed',
                    $oldStatus,
                    OrderStatus::Confirmed,
                    $description ?? 'Order confirmed'
                );

                OrderConfirmed::dispatch($order);

                Log::info('Order confirmed', [
                    'order_id' => $order->id,
                    'confirmation_number' => $order->confirmation_number,
                ]);

                return $order;
            });
        } catch (\Exception $e) {
            Log::error('Order confirmation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Log order state changes for audit trail.
     */
    private function logOrderAction(
        Order $order,
        string $action,
        ?OrderStatus $oldStatus,
        OrderStatus $newStatus,
        ?string $description = null
    ): void {
        try {
            OrderLog::create([
                'order_id' => $order->id,
                'action' => $action,
                'old_status' => $oldStatus?->value,
                'new_status' => $newStatus->value,
                'description' => $description,
                'user_id' => Auth::id(),
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log order action', [
                'order_id' => $order->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
