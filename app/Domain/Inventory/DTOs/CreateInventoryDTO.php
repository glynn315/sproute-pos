<?php

namespace App\Domain\Inventory\DTOs;

use App\Http\Requests\Inventory\AdjustStockRequest;

readonly class CreateInventoryDTO
{
    public function __construct(
        public int     $productId,
        public int     $quantity,
        public string  $type,
        public ?string $notes,
    ) {}

    public static function fromRequest(AdjustStockRequest $request): self
    {
        return new self(
            productId: $request->validated('product_id'),
            quantity:  (int) $request->validated('quantity'),
            type:      $request->validated('type', 'adjustment'),
            notes:     $request->validated('notes'),
        );
    }
}
