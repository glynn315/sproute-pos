<?php

namespace App\Http\Requests\Eatery\Tables;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->canManage() ?? false);
    }

    public function rules(): array
    {
        return [
            'table_number' => ['required', 'integer', 'min:1', 'max:9999'],
            'label'        => ['nullable', 'string', 'max:80'],
            'seats'        => ['nullable', 'integer', 'min:1', 'max:50'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ];
    }
}
