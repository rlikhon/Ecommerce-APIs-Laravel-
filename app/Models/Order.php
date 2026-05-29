<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'address', 'mobile', 'state', 'zip', 'city',
        'grand_total', 'sub_total', 'discount', 'shipping',
        'payment_method', 'payment_status', 'status', 'user_id',
        'confirmation_number', 'processed_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'grand_total' => 'float',
        'sub_total' => 'float',
        'discount' => 'float',
        'shipping' => 'float',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
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
