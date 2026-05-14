<?php

namespace App\Http\Requests\Eatery\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->canManage() ?? false);
    }

    public function rules(): array
    {
        return [
            'category_id'  => ['nullable', 'integer', 'exists:categories,id'],
            'name'         => ['required', 'string', 'max:150'],
            'description'  => ['nullable', 'string', 'max:500'],
            'category'     => ['nullable', 'string', 'max:80'],
            'price'        => ['required', 'numeric', 'min:0'],
            'image_url'    => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', 'boolean'],
        ];
    }
}
