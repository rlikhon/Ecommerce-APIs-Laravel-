<?php

namespace App\Http\Controllers\front;

use App\DataTransferObjects\OrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    /**
     * Return paginated orders for the authenticated user.
     *
     * GET /api/account/order
     */
    public function index(): JsonResponse
    {
        $page = (int) request()->query('page', 1);
        $perPage = min((int) request()->query('per_page', 15), 100);

        $result = $this->orderService->getUserOrders(auth()->user(), $page, $perPage);

        return response()->json([
            'data' => OrderResource::collection($result['items']),
            'pagination' => $result['pagination'],
        ]);
    }

    /**
     * Create a new order.
     *
     * POST /api/account/order
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $dto = OrderDTO::fromRequest($request);
        $result = $this->orderService->createOrder($dto);

        return response()->json($result['response'], $result['status']);
    }

    /**
     * Update one or more orders to a new active status.
     *
     * PATCH /api/account/order/status
     */
    public function updateStatus(UpdateOrderStatusRequest $request): JsonResponse
    {
        $orderIds = $request->input('order_ids');
        $newStatus = $request->input('status');

        $result = $this->orderService->updateOrderStatus($orderIds, $newStatus, auth()->user());

        return response()->json($result['response'], $result['status']);
    }

    /**
     * Get order detail for the authenticated user.
     *
     * GET /api/account/order/{id}
     */
    public function show(string $id): JsonResponse
    {
        $orderId = (int) $id;
        $order = $this->orderService->getUserOrder(auth()->user(), $orderId);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json(new OrderResource($order));
    }
}
