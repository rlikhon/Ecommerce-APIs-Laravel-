<?php

namespace App\Services;

use App\Exceptions\Wishlist\ProductNotFoundException;
use App\Exceptions\Wishlist\WishlistItemNotFoundException;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function getUserWishlists(User $user): Collection
    {
        return Wishlist::where('user_id', $user->id)
            ->with('product:id,title,price,compare_price,image')
            ->latest()
            ->get();
    }

    public function addItem(User $user, int $productId): Wishlist
    {
        if (!Product::find($productId)) {
            throw new ProductNotFoundException($productId);
        }

        $wishlist = Wishlist::firstOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $productId,
            ]
        );

        return $wishlist->load('product:id,title,price,compare_price,image');
    }

    public function removeItem(User $user, int $productId): void
    {
        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (!$wishlist) {
            throw new WishlistItemNotFoundException();
        }

        $wishlist->delete();
    }

    public function isItemInWishlist(User $user, int $productId): bool
    {
        return Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();
    }
}
