<?php

namespace App\Http\Requests\Admin\Modules;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() ?? false);
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:modules,name'],
            'display_name' => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:500'],
            'icon'         => ['nullable', 'string', 'max:80'],
            'is_active'    => ['nullable', 'boolean'],
            'sort_order'   => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Slug must be lowercase letters, digits, or underscores (must start with a letter).',
        ];
    }
}
