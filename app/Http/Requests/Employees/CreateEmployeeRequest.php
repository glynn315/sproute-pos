<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canAdminTenant() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'pin'      => ['nullable', 'string', 'digits_between:4,6'],
            'role'     => ['sometimes', Rule::in(['manager', 'cashier'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (! $this->input('password') && ! $this->input('pin')) {
                $v->errors()->add('auth', 'Employee must have either a password or a PIN.');
            }
        });
    }
}
