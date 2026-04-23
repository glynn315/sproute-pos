<?php

namespace App\Domain\Products\DTOs;

use App\Http\Requests\Products\CreateProductRequest;

readonly class CreateProductsDTO
{
    public function __construct(
        public int     $tenantId,
        public ?int    $categoryId,
        public string  $name,
        public ?string $description,
        public ?string $sku,
        public ?string $barcode,
        public float   $price,
        public float   $costPrice,
        public int     $stockQuantity,
        public int     $reorderLevel,
        public ?string $imageUrl,
    ) {}

    public static function fromRequest(CreateProductRequest $request): self
    {
        return new self(
            tenantId:      auth()->user()->tenant_id,
            categoryId:    $request->validated('category_id'),
            name:          $request->validated('name'),
            description:   $request->validated('description'),
            sku:           $request->validated('sku'),
            barcode:       $request->validated('barcode'),
            price:         (float) $request->validated('price'),
            costPrice:     (float) ($request->validated('cost_price') ?? 0),
            stockQuantity: (int) ($request->validated('stock_quantity') ?? 0),
            reorderLevel:  (int) ($request->validated('reorder_level') ?? 5),
            imageUrl:      $request->validated('image_url'),
        );
    }
}
