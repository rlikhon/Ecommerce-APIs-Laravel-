<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::enum(OrderStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Invalid order status. Allowed values: '.implode(', ', array_column(OrderStatus::cases(), 'value')),
        ];
    }
}
