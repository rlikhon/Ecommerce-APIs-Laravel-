<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'product' => [
                'id' => $this->product?->id,
                'title' => $this->product?->title,
                'price' => $this->product?->price,
                'compare_price' => $this->product?->compare_price,
                'image' => $this->product?->image,
            ],
            'added_at' => $this->created_at,
        ];
    }
}
