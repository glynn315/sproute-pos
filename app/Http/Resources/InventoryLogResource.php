<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'product'          => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
            ]),
            'performed_by'     => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'type'             => $this->type,
            'quantity_change'  => $this->quantity_change,
            'quantity_before'  => $this->quantity_before,
            'quantity_after'   => $this->quantity_after,
            'notes'            => $this->notes,
            'reference_id'     => $this->reference_id,
            'reference_type'   => $this->reference_type,
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
