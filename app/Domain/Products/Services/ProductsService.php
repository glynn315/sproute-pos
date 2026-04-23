<?php

namespace App\Domain\Products\Services;

use App\Domain\Products\DTOs\CreateProductsDTO;
use App\Domain\Products\DTOs\UpdateProductDTO;
use App\Domain\Products\Repositories\ProductsRepository;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Traits\AuditLogger;
use Illuminate\Validation\ValidationException;

class ProductsService
{
    use AuditLogger;

    public function __construct(private readonly ProductsRepository $repo) {}

    public function create(CreateProductsDTO $dto): Product
    {
        $this->ensureSkuUnique($dto->tenantId, $dto->sku);
        $this->ensureBarcodeUnique($dto->tenantId, $dto->barcode);

        $product = $this->repo->create([
            'tenant_id'      => $dto->tenantId,
            'category_id'    => $dto->categoryId,
            'name'           => $dto->name,
            'description'    => $dto->description,
            'sku'            => $dto->sku,
            'barcode'        => $dto->barcode,
            'price'          => $dto->price,
            'cost_price'     => $dto->costPrice,
            'stock_quantity' => $dto->stockQuantity,
            'reorder_level'  => $dto->reorderLevel,
            'image_url'      => $dto->imageUrl,
            'is_active'      => true,
        ]);

        // Log initial stock if provided
        if ($dto->stockQuantity > 0) {
            InventoryLog::create([
                'tenant_id'       => $dto->tenantId,
                'product_id'      => $product->id,
                'user_id'         => auth()->id(),
                'type'            => 'initial',
                'quantity_change' => $dto->stockQuantity,
                'quantity_before' => 0,
                'quantity_after'  => $dto->stockQuantity,
                'notes'           => 'Initial stock',
            ]);
        }

        $this->auditModel('created', $product);

        return $product->load('category');
    }

    public function update(Product $product, UpdateProductDTO $dto): Product
    {
        $old = $product->toArray();

        $this->ensureSkuUnique($product->tenant_id, $dto->sku, $product->id);
        $this->ensureBarcodeUnique($product->tenant_id, $dto->barcode, $product->id);

        $updated = $this->repo->update($product, array_filter([
            'category_id'   => $dto->categoryId,
            'name'          => $dto->name,
            'description'   => $dto->description,
            'sku'           => $dto->sku,
            'barcode'       => $dto->barcode,
            'price'         => $dto->price,
            'cost_price'    => $dto->costPrice,
            'reorder_level' => $dto->reorderLevel,
            'image_url'     => $dto->imageUrl,
            'is_active'     => $dto->isActive,
        ], fn ($v) => $v !== null));

        $this->auditModel('updated', $updated, $old);

        return $updated;
    }

    public function adjustStock(Product $product, int $quantity, string $type, ?string $notes = null): Product
    {
        $before = $product->stock_quantity;
        $after  = $before + $quantity;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'quantity' => ["Insufficient stock. Available: {$before}"],
            ]);
        }

        $product->update(['stock_quantity' => $after]);

        InventoryLog::create([
            'tenant_id'       => $product->tenant_id,
            'product_id'      => $product->id,
            'user_id'         => auth()->id(),
            'type'            => $type,
            'quantity_change' => $quantity,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'notes'           => $notes,
        ]);

        $this->audit('stock_adjusted', 'Product', $product->id, ['stock' => $before], ['stock' => $after]);

        return $product->fresh();
    }

    public function delete(Product $product): void
    {
        $this->audit('deleted', 'Product', $product->id, $product->toArray());
        $this->repo->delete($product);
    }

    private function ensureSkuUnique(int $tenantId, ?string $sku, ?int $excludeId = null): void
    {
        if (! $sku) {
            return;
        }
        $query = Product::where('tenant_id', $tenantId)->where('sku', $sku);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['sku' => ['SKU already exists for this store.']]);
        }
    }

    private function ensureBarcodeUnique(int $tenantId, ?string $barcode, ?int $excludeId = null): void
    {
        if (! $barcode) {
            return;
        }
        $query = Product::where('tenant_id', $tenantId)->where('barcode', $barcode);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['barcode' => ['Barcode already exists for this store.']]);
        }
    }
}
