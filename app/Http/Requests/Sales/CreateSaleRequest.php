<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.discount'       => ['nullable', 'numeric', 'min:0'],
            'payment_method'         => ['required', Rule::in(['cash', 'card', 'gcash', 'maya', 'bank_transfer', 'other'])],
            'amount_paid'            => ['required', 'numeric', 'min:0'],
            'discount_amount'        => ['nullable', 'numeric', 'min:0'],
            'tax_amount'             => ['nullable', 'numeric', 'min:0'],
            'notes'                  => ['nullable', 'string', 'max:500'],
        ];
    }
}
