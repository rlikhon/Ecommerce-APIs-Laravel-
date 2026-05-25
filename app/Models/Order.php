<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'address', 'mobile', 'state', 'zip', 'city',
        'grand_total', 'sub_total', 'discount', 'shipping',
        'payment_method', 'payment_status', 'status', 'user_id'
    ];

    /**
     * Get the order items for the order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Helper to expose a compact representation for the order.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'items' => $this->orderItems->toArray(),
        ]);
    }
}
