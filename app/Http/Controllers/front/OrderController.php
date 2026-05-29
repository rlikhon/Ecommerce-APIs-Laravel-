<?php

namespace App\Http\Controllers\front;

use App\DataTransferObjects\OrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
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
}
