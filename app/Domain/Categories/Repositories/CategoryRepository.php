<?php

namespace App\Domain\Categories\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function allForTenant(int $tenantId): Collection
    {
        return Category::withCount('products')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();
    }

    public function findForTenant(int $tenantId, int $id): ?Category
    {
        return Category::where('tenant_id', $tenantId)->find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        $category->products()->update(['category_id' => null]);
        $category->delete();
    }
}
