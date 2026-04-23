<?php

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;

class CreateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public function rules(): array
    {
        return [
            'category'     => ['required', 'string', 'max:100'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'description'  => ['nullable', 'string', 'max:500'],
            'expense_date' => ['required', 'date', 'date_format:Y-m-d'],
            'receipt_url'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
