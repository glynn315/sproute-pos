<?php

namespace App\Http\Requests\Eatery\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'order_id'       => ['required_without:table_id', 'nullable', 'integer', 'exists:orders,order_id'],
            'table_id'       => ['required_without:order_id', 'nullable', 'integer', 'exists:restaurant_tables,restaurant_table_id'],
            'cash_received'  => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'gcash', 'maya', 'bank_transfer', 'other'])],
            'reference'      => ['nullable', 'string', 'max:80'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
