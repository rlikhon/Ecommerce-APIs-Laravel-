<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'address' => $this->address,
            'mobile' => $this->mobile,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'grand_total' => (float) $this->grand_total,
            'sub_total' => (float) $this->sub_total,
            'discount' => (float) $this->discount,
            'shipping' => (float) $this->shipping,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
