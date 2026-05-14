<?php

namespace App\Domain\Suppliers\Repositories;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository
{
    public function paginateForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Supplier::withCount('products')
            ->where('tenant_id', $tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('contact_name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function allActiveForTenant(int $tenantId): Collection
    {
        return Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findForTenant(int $tenantId, int $id): ?Supplier
    {
        return Supplier::where('tenant_id', $tenantId)->find($id);
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update(array_filter($data, fn ($v) => $v !== null));
        return $supplier->fresh();
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->products()->update(['supplier_id' => null]);
        $supplier->update(['is_active' => false]);
    }
}
