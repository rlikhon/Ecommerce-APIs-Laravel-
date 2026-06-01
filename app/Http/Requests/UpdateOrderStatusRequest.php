<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $allowedStatuses = array_map(
            fn (OrderStatus $status): string => $status->value,
            OrderStatus::cases()
        );

        return [
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'status' => ['required', 'string', Rule::in($allowedStatuses)],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'error' => 'VALIDATION_FAILED',
            'message' => 'The given data was invalid.',
            'details' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
