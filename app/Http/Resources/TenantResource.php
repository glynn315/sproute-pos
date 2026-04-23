<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'address'             => $this->address,
            'logo_url'            => $this->logo_url,
            'primary_color'       => $this->primary_color,
            'secondary_color'     => $this->secondary_color,
            'status'              => $this->status,
            'is_verified'         => $this->isVerified(),
            'subscription_plan'   => new SubscriptionPlanResource($this->whenLoaded('subscriptionPlan')),
            'subscription_ends_at'=> $this->subscription_ends_at?->toIso8601String(),
            'has_active_subscription' => $this->hasActiveSubscription(),
            'created_at'          => $this->created_at->toIso8601String(),
        ];
    }
}
