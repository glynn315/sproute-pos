<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id'    => ['nullable', 'integer', 'exists:categories,id'],
            'name'           => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'sku'            => ['nullable', 'string', 'max:100'],
            'barcode'        => ['nullable', 'string', 'max:100'],
            'price'          => ['required', 'numeric', 'min:0'],
            'cost_price'     => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'reorder_level'  => ['nullable', 'integer', 'min:0'],
            'image_url'      => ['nullable', 'string', 'max:500'],
        ];
    }
}
