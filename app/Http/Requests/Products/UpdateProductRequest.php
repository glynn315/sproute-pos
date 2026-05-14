<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id'     => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'supplier_id'     => ['sometimes', 'nullable', 'integer', 'exists:suppliers,id'],
            'name'            => ['sometimes', 'string', 'max:150'],
            'description'     => ['sometimes', 'nullable', 'string', 'max:1000'],
            'sku'             => ['sometimes', 'nullable', 'string', 'max:100'],
            'barcode'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'price'           => ['sometimes', 'numeric', 'min:0'],
            'cost_price'      => ['sometimes', 'numeric', 'min:0'],
            'reorder_level'   => ['sometimes', 'integer', 'min:0'],
            'expiration_date' => ['sometimes', 'nullable', 'date'],
            'image_url'       => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_active'       => ['sometimes', 'boolean'],
        ];
    }
}
