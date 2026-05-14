<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public function rules(): array
    {
        $methods = ['cash', 'card', 'gcash', 'maya', 'bank_transfer', 'other'];

        return [
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.sale_item_id'   => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'reason'                 => ['required', 'string', 'max:500'],
            'refund_method'          => ['nullable', Rule::in($methods)],
        ];
    }
}
