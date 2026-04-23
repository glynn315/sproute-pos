<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'recorded_by'  => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'category'     => $this->category,
            'amount'       => (float) $this->amount,
            'description'  => $this->description,
            'expense_date' => $this->expense_date->toDateString(),
            'receipt_url'  => $this->receipt_url,
            'created_at'   => $this->created_at->toIso8601String(),
        ];
    }
}
