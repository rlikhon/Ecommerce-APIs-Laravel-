<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistRequest;
use App\Http\Requests\DestroyWishlistRequest;
use App\Http\Resources\WishlistResource;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlistService) {}

    public function index(): JsonResponse
    {
        $wishlists = $this->wishlistService->getUserWishlists(auth()->user());

        return response()->json([
            'data' => WishlistResource::collection($wishlists),
        ]);
    }

    public function store(StoreWishlistRequest $request): JsonResponse
    {
        $wishlist = $this->wishlistService->addItem(
            $request->user(),
            $request->product_id
        );

        $isNew = $wishlist->wasRecentlyCreated;

        return response()->json([
            'data' => new WishlistResource($wishlist),
        ], $isNew ? 201 : 200);
    }

    public function destroy(DestroyWishlistRequest $request): JsonResponse
    {
        $this->wishlistService->removeItem(
            $request->user(),
            $request->product_id
        );

        return response()->json([
            'message' => 'Item removed from wishlist successfully',
        ]);
    }
}

