<?php

namespace App\Http\Requests\Eatery\Tables;

use App\Domain\Eatery\Tables\Models\RestaurantTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->canManage() ?? false);
    }

    public function rules(): array
    {
        return [
            'table_number' => ['sometimes', 'integer', 'min:1', 'max:9999'],
            'label'        => ['sometimes', 'nullable', 'string', 'max:80'],
            'seats'        => ['sometimes', 'integer', 'min:1', 'max:50'],
            'status'       => ['sometimes', Rule::in([
                RestaurantTable::STATUS_AVAILABLE,
                RestaurantTable::STATUS_OCCUPIED,
                RestaurantTable::STATUS_NOT_YET_PAID,
            ])],
            'notes'        => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
