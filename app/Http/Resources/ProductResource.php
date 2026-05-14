<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'category'        => new CategoryResource($this->whenLoaded('category')),
            'category_id'     => $this->category_id,
            'supplier'        => new SupplierResource($this->whenLoaded('supplier')),
            'supplier_id'     => $this->supplier_id,
            'name'            => $this->name,
            'description'     => $this->description,
            'sku'             => $this->sku,
            'barcode'         => $this->barcode,
            'price'           => (float) $this->price,
            'cost_price'      => (float) $this->cost_price,
            'stock_quantity'  => $this->stock_quantity,
            'reorder_level'   => $this->reorder_level,
            'expiration_date' => $this->expiration_date?->toIso8601String(),
            'image_url'       => $this->image_url,
            'is_active'       => $this->is_active,
            'is_low_stock'    => $this->isLowStock(),
            'is_out_of_stock' => $this->isOutOfStock(),
            'created_at'      => $this->created_at->toIso8601String(),
            'updated_at'      => $this->updated_at->toIso8601String(),
        ];
    }
}
