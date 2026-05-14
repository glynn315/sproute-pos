<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_item_id' => $this->order_item_id,
            'order_id'      => $this->order_id,
            'menu_item_id'  => $this->menu_item_id,
            'item_name'     => $this->item_name,
            'price'         => (float) $this->price,
            'quantity'      => (int) $this->quantity,
            'subtotal'      => (float) $this->subtotal,
            'notes'         => $this->notes,
        ];
    }
}
