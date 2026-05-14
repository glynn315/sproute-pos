<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'module_id'    => $this->module_id,
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'description'  => $this->description,
            'icon'         => $this->icon,
            'is_active'    => (bool) $this->is_active,
            'sort_order'   => (int) $this->sort_order,
            'created_at'   => optional($this->created_at)->toIso8601String(),
            'updated_at'   => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
