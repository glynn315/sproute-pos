<?php

namespace App\Http\Requests\Tenants;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canAdminTenant() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:150'],
            'phone'           => ['sometimes', 'nullable', 'string', 'max:20'],
            'address'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'logo_url'        => ['sometimes', 'nullable', 'string', 'max:500'],
            'primary_color'   => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
