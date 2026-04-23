<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'not_in:0'],
            'type'       => ['required', Rule::in(['purchase', 'adjustment', 'return'])],
            'notes'      => ['nullable', 'string', 'max:500'],
        ];
    }
}
