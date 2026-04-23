<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'transaction_number' => $this->transaction_number,
            'cashier'            => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'subtotal'           => (float) $this->subtotal,
            'discount_amount'    => (float) $this->discount_amount,
            'tax_amount'         => (float) $this->tax_amount,
            'total'              => (float) $this->total,
            'amount_paid'        => (float) $this->amount_paid,
            'change_amount'      => (float) $this->change_amount,
            'payment_method'     => $this->payment_method,
            'status'             => $this->status,
            'notes'              => $this->notes,
            'items'              => SaleItemResource::collection($this->whenLoaded('items')),
            'items_count'        => $this->whenLoaded('items', fn () => $this->items->count()),
            'created_at'         => $this->created_at->toIso8601String(),
        ];
    }
}
