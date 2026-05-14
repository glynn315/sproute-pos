<?php

namespace App\Http\Requests\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:150'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'phone'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'email'        => ['sometimes', 'nullable', 'email', 'max:255'],
            'address'      => ['sometimes', 'nullable', 'string', 'max:500'],
            'notes'        => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }
}
