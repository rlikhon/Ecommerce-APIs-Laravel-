<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'name', 'quantity', 'price', 'unit_price', 'size'
    ];

    /**
     * The order this item belongs to.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The product this item refers to.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
