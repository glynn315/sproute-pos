<?php

namespace App\Http\Requests\Admin\Modules;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() ?? false);
    }

    public function rules(): array
    {
        return [
            // name (slug) is immutable — changing it would break tenant.modules JSON references.
            'display_name' => ['sometimes', 'string', 'max:100'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'icon'         => ['sometimes', 'nullable', 'string', 'max:80'],
            'is_active'    => ['sometimes', 'boolean'],
            'sort_order'   => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
