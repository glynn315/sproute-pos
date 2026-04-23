<?php

namespace App\Domain\Products\DTOs;

use App\Http\Requests\Products\UpdateProductRequest;

readonly class UpdateProductDTO
{
    public function __construct(
        public ?int    $categoryId,
        public ?string $name,
        public ?string $description,
        public ?string $sku,
        public ?string $barcode,
        public ?float  $price,
        public ?float  $costPrice,
        public ?int    $reorderLevel,
        public ?string $imageUrl,
        public ?bool   $isActive,
    ) {}

    public static function fromRequest(UpdateProductRequest $request): self
    {
        return new self(
            categoryId:   $request->validated('category_id'),
            name:         $request->validated('name'),
            description:  $request->validated('description'),
            sku:          $request->validated('sku'),
            barcode:      $request->validated('barcode'),
            price:        $request->validated('price') !== null ? (float) $request->validated('price') : null,
            costPrice:    $request->validated('cost_price') !== null ? (float) $request->validated('cost_price') : null,
            reorderLevel: $request->validated('reorder_level') !== null ? (int) $request->validated('reorder_level') : null,
            imageUrl:     $request->validated('image_url'),
            isActive:     $request->validated('is_active'),
        );
    }
}
