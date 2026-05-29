<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $validPaymentMethods = ['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'cash_on_delivery'];
        $validPaymentStatuses = ['pending', 'completed', 'failed', 'refunded'];
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'failed'];

        return [
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'mobile' => 'required|string|regex:/^[0-9\+\-\s\(\)]{7,20}$/',
            'state' => 'required|string|max:100',
            'zip' => 'required|string|max:20|regex:/^[a-zA-Z0-9\-\s]+$/',
            'city' => 'required|string|max:100',
            'grand_total' => 'required|numeric|min:0|max:999999.99',
            'sub_total' => 'required|numeric|min:0|max:999999.99',
            'discount' => 'required|numeric|min:0|max:999999.99',
            'shipping_charges' => 'required|numeric|min:0|max:9999.99',
            'payment_method' => ['required', 'string', Rule::in($validPaymentMethods)],
            'payment_status' => ['required', 'string', Rule::in($validPaymentStatuses)],
            'status' => ['required', 'string', Rule::in($validStatuses)],
            'cart' => 'required|array|min:1|max:100',
            'cart.*.product_id' => 'required|integer|exists:products,id',
            'cart.*.title' => 'required|string|max:255',
            'cart.*.qty' => 'required|integer|min:1|max:999',
            'cart.*.price' => 'required|numeric|min:0|max:999999.99',
            'cart.*.size' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Invalid mobile number format',
            'zip.regex' => 'Invalid zip code format',
            'name.regex' => 'Name should only contain letters and spaces',
            'cart.min' => 'Cart must contain at least one item',
            'cart.max' => 'Cart cannot contain more than 100 items',
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
