<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreOrderRequest;
use App\DTO\OrderDTO;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    /**
     * Return all orders for the authenticated user.
     *
     * GET /api/account/order
     */
    public function index(): JsonResponse
    {
        $orders = $this->orderService->getUserOrders(auth()->user());
        
        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }
    
    public function store(StoreOrderRequest $request): JsonResponse
    {
        // 1. Transform validated request data to a reliable DTO
        $dto = OrderDTO::fromRequest($request);

        // 2. Offload processing to your backend service layer
        $order = $this->orderService->createOrder($dto);

        // 3. Return an API standard formatted response
        return response()->json([
            'message' => 'Order placed successfully', 
            'order_id' => $order->id,
            'status' => 201
        ], 201);
    }   
}
