<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'restaurant_table_id' => $this->restaurant_table_id,
            'table_number'        => (int) $this->table_number,
            'label'               => $this->label,
            'seats'               => (int) $this->seats,
            'status'              => $this->status,
            'notes'               => $this->notes,
            'active_order'        => $this->whenLoaded('activeOrder', fn () => $this->activeOrder
                ? new OrderResource($this->activeOrder)
                : null),
            'created_at'          => optional($this->created_at)->toIso8601String(),
            'updated_at'          => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
