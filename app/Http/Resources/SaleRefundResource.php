<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleRefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'sale_id'        => $this->sale_id,
            'total_refunded' => (float) $this->total_refunded,
            'reason'         => $this->reason,
            'refund_method'  => $this->refund_method,
            'refunded_by'    => $this->whenLoaded('refundedBy', fn () => [
                'id'   => $this->refundedBy->id,
                'name' => $this->refundedBy->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id'           => $i->id,
                'sale_item_id' => $i->sale_item_id,
                'quantity'     => $i->quantity,
                'amount'       => (float) $i->amount,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
