<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'display_name'  => $this->display_name,
            'price'         => (float) $this->price,
            'billing_cycle' => $this->billing_cycle,
            'max_employees' => $this->max_employees,
            'max_products'  => $this->max_products,
            'features'      => $this->features,
            'is_active'     => $this->is_active,
        ];
    }
}
