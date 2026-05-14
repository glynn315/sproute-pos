<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'menu_item_id' => $this->menu_item_id,
            'category_id'  => $this->category_id,
            'name'         => $this->name,
            'description'  => $this->description,
            'category'     => $this->category,
            'price'        => (float) $this->price,
            'image_url'    => $this->image_url,
            'availability' => (bool) $this->availability,
            'created_at'   => optional($this->created_at)->toIso8601String(),
            'updated_at'   => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
