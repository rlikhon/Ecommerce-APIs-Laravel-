<?php

namespace App\DataTransferObjects;

use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Str;

class OrderDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $address,
        public readonly string $mobile,
        public readonly string $state,
        public readonly string $zip,
        public readonly string $city,
        public readonly float $grand_total,
        public readonly float $sub_total,
        public readonly float $discount,
        public readonly float $shipping,
        public readonly string $payment_method,
        public readonly string $payment_status,
        public readonly string $status,
        public readonly int $user_id,
        public readonly array $cart,
        public readonly string $confirmation_number = '',
    ) {}

    public static function fromRequest(StoreOrderRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            address: $request->validated('address'),
            mobile: $request->validated('mobile'),
            state: $request->validated('state'),
            zip: $request->validated('zip'),
            city: $request->validated('city'),
            grand_total: (float) $request->validated('grand_total'),
            sub_total: (float) $request->validated('sub_total'),
            discount: (float) $request->validated('discount'),
            shipping: (float) $request->validated('shipping_charges'),
            payment_method: $request->validated('payment_method'),
            payment_status: $request->validated('payment_status'),
            status: $request->validated('status'),
            user_id: auth()->id(),
            cart: $request->validated('cart'),
            confirmation_number: self::generateConfirmationNumber(),
        );
    }

    public static function generateConfirmationNumber(): string
    {
        return 'ORD-'.strtoupper(Str::random(4)).'-'.date('YmdHis').'-'.random_int(1000, 9999);
    }
}
