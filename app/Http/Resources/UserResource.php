<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'role'            => $this->role,
            'is_active'       => $this->is_active,
            'last_login_at'   => $this->last_login_at?->toIso8601String(),
            'email_verified'  => $this->email_verified_at !== null,
            'has_pin'         => $this->getOriginal('pin') !== null || $this->pin !== null,
            'tenant'          => new TenantResource($this->whenLoaded('tenant')),
            'created_at'      => $this->created_at->toIso8601String(),
        ];
    }
}
