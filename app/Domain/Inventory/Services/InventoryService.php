<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\DTOs\CreateInventoryDTO;
use App\Domain\Products\Services\ProductsService;
use App\Models\Product;

class InventoryService
{
    public function __construct(private readonly ProductsService $productService) {}

    public function adjust(CreateInventoryDTO $dto, int $tenantId): Product
    {
        $product = Product::where('tenant_id', $tenantId)
            ->where('id', $dto->productId)
            ->firstOrFail();

        return $this->productService->adjustStock($product, $dto->quantity, $dto->type, $dto->notes);
    }
}
