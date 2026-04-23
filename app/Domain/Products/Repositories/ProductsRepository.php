<?php

namespace App\Domain\Products\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductsRepository
{
    public function paginateForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Product::with('category')
            ->where('tenant_id', $tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('sku', 'like', "%{$s}%")
                ->orWhere('barcode', 'like', "%{$s}%"));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function allActiveForTenant(int $tenantId): Collection
    {
        return Product::with('category')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findForTenant(int $tenantId, int $productId): ?Product
    {
        return Product::where('tenant_id', $tenantId)->find($productId);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update(array_filter($data, fn ($v) => $v !== null));
        return $product->fresh('category');
    }

    public function delete(Product $product): void
    {
        $product->update(['is_active' => false]);
    }

    public function lowStockForTenant(int $tenantId): Collection
    {
        return Product::with('category')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->orderBy('stock_quantity')
            ->get();
    }
}
