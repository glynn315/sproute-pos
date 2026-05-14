<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommitSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $methods = ['cash', 'card', 'gcash', 'maya', 'bank_transfer', 'other'];

        return [
            'payment_method'             => ['required', Rule::in($methods)],
            'amount_paid'                => ['required', 'numeric', 'min:0'],
            'discount_amount'            => ['nullable', 'numeric', 'min:0'],
            'tax_amount'                 => ['nullable', 'numeric', 'min:0'],
            'notes'                      => ['nullable', 'string', 'max:500'],
            'payments'                   => ['nullable', 'array'],
            'payments.*.payment_method'  => ['required_with:payments', Rule::in($methods)],
            'payments.*.amount'          => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.reference_no'    => ['nullable', 'string', 'max:100'],
        ];
    }
}
