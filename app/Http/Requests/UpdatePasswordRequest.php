<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            // Enforces strong password constraints for admin portals
            'new_password'     => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}

