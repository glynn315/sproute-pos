<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canAdminTenant() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:100'],
            'email'     => ['sometimes', 'email', 'max:150', 'unique:users,email,' . $this->route('employee')],
            'password'  => ['sometimes', 'nullable', 'string', 'min:8'],
            'pin'       => ['sometimes', 'nullable', 'string', 'digits_between:4,6'],
            'role'      => ['sometimes', Rule::in(['manager', 'cashier'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
