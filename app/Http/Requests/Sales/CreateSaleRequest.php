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
        $asDraft = (bool) $this->input('as_draft', false);
        $methods = ['cash', 'card', 'gcash', 'maya', 'bank_transfer', 'other'];

        return [
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_id'         => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'           => ['required', 'integer', 'min:1'],
            'items.*.discount'           => ['nullable', 'numeric', 'min:0'],
            'payment_method'             => [$asDraft ? 'nullable' : 'required', Rule::in($methods)],
            'amount_paid'                => [$asDraft ? 'nullable' : 'required', 'numeric', 'min:0'],
            'discount_amount'            => ['nullable', 'numeric', 'min:0'],
            'tax_amount'                 => ['nullable', 'numeric', 'min:0'],
            'notes'                      => ['nullable', 'string', 'max:500'],
            'as_draft'                   => ['nullable', 'boolean'],
            'payments'                   => ['nullable', 'array'],
            'payments.*.payment_method'  => ['required_with:payments', Rule::in($methods)],
            'payments.*.amount'          => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.reference_no'    => ['nullable', 'string', 'max:100'],
        ];
    }
}
