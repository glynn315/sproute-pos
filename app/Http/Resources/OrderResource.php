<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id'            => $this->order_id,
            'order_number'        => $this->order_number,
            'restaurant_table_id' => $this->restaurant_table_id,
            'table'               => $this->whenLoaded('table', fn () => [
                'restaurant_table_id' => $this->table->restaurant_table_id,
                'table_number'        => (int) $this->table->table_number,
                'label'               => $this->table->label,
            ]),
            'cashier'             => $this->whenLoaded('cashier', fn () => $this->cashier ? [
                'id'   => $this->cashier->id,
                'name' => $this->cashier->name,
            ] : null),
            'subtotal'            => (float) $this->subtotal,
            'total_amount'        => (float) $this->total_amount,
            'payment_status'      => $this->payment_status,
            'notes'               => $this->notes,
            'items'               => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count'         => $this->whenLoaded('items', fn () => $this->items->count()),
            'payment'             => $this->whenLoaded('payment', fn () => $this->payment
                ? new PaymentResource($this->payment)
                : null),
            'created_at'          => optional($this->created_at)->toIso8601String(),
            'updated_at'          => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
