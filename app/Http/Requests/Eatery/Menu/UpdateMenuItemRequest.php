<?php

namespace App\Http\Requests\Eatery\Menu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->canManage() ?? false);
    }

    public function rules(): array
    {
        return [
            'category_id'  => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'name'         => ['sometimes', 'string', 'max:150'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'category'     => ['sometimes', 'nullable', 'string', 'max:80'],
            'price'        => ['sometimes', 'numeric', 'min:0'],
            'image_url'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'availability' => ['sometimes', 'boolean'],
        ];
    }
}
