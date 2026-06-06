<?php

namespace App\Http\Controllers\admin;

use App\Enums\OrderStatus;
use App\Exceptions\Order\InvalidOrderDataException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmOrderRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    /**
     * Get all orders with pagination.
     *
     * GET /api/admin/orders
     */
    public function index(): JsonResponse
    {
        $page = (int) request()->query('page', 1);
        $perPage = min((int) request()->query('per_page', 15), 100);
        $status = request()->query('status');

        $query = Order::with('user', 'orderItems')
            ->latest('created_at');

        if ($status) {
            try {
                $query->where('status', OrderStatus::from($status)->value);
            } catch (\ValueError) {
                return response()->json([
                    'message' => "Invalid status: {$status}",
                ], 400);
            }
        }

        $orders = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'has_more' => $orders->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get a specific order by ID.
     *
     * GET /api/admin/orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with('user', 'orderItems', 'logs')
            ->find($id);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json(new OrderResource($order));
    }

    /**
     * Confirm an order (transitions from pending to confirmed).
     * Triggers OrderConfirmed event for notifications.
     *
     * POST /api/admin/orders/{id}/confirm
     */
    public function confirm(int $id, ConfirmOrderRequest $request): JsonResponse
    {
        $order = Order::find($id);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        try {
            $confirmed = $this->orderService->confirmOrder(
                $order,
                $request->input('description')
            );

            return response()->json([
                'message' => 'Order confirmed successfully.',
                'order' => new OrderResource($confirmed),
            ]);
        } catch (InvalidOrderDataException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to confirm order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order status (all statuses except confirmed).
     * Triggers event when status changes.
     *
     * PATCH /api/admin/orders/{id}/status
     */
    public function updateStatus(int $id, UpdateOrderStatusRequest $request): JsonResponse
    {
        $order = Order::find($id);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $newStatus = $request->input('status');

        // Prevent using this endpoint for confirmation
        if ($newStatus === OrderStatus::Confirmed->value) {
            return response()->json([
                'message' => 'Use the /confirm endpoint to confirm orders.',
            ], 400);
        }

        try {
            $oldStatus = $order->status;

            // Validate transition
            if (! $oldStatus->canTransitionTo(OrderStatus::from($newStatus))) {
                return response()->json([
                    'message' => "Order {$id} cannot transition from {$oldStatus->value} to {$newStatus}.",
                ], 422);
            }

            // Update order directly (admin bypass)
            $order->update(['status' => $newStatus]);

            // Log the action
            $order->logs()->create([
                'action' => 'status_updated',
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus,
                'description' => "Order status updated to {$newStatus}",
                'user_id' => auth()->id(),
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update order status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
