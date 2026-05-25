<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'fullName' => 'required|string|max:255',
            'bio'      => 'nullable|string|max:250',
            'location' => 'nullable|string|max:255',
        ];
    }
}

