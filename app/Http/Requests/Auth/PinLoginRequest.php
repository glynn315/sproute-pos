<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PinLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email'],
            'pin'         => ['required', 'string', 'digits_between:4,6'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
