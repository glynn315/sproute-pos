<?php

namespace App\Http\Requests\Eatery\Orders;

use Illuminate\Foundation\Http\FormRequest;

class AddOrderItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items'                => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,menu_item_id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.notes'        => ['nullable', 'string', 'max:200'],
        ];
    }
}
