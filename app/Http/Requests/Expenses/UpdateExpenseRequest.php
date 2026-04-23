<?php

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public function rules(): array
    {
        return [
            'category'     => ['sometimes', 'string', 'max:100'],
            'amount'       => ['sometimes', 'numeric', 'min:0.01'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'expense_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'receipt_url'  => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
