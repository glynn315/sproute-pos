<?php

namespace App\Domain\Eatery\Tables\Repositories;

use App\Domain\Eatery\Tables\Models\RestaurantTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TableRepository
{
    public function listForTenant(int $tenantId, array $filters = []): Collection
    {
        return $this->buildQuery($tenantId, $filters)->orderBy('table_number')->get();
    }

    public function paginateForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->buildQuery($tenantId, $filters)->orderBy('table_number')->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $tableId): ?RestaurantTable
    {
        return RestaurantTable::with('activeOrder.items')
            ->where('tenant_id', $tenantId)
            ->where('restaurant_table_id', $tableId)
            ->first();
    }

    public function create(array $data): RestaurantTable
    {
        return RestaurantTable::create($data);
    }

    private function buildQuery(int $tenantId, array $filters)
    {
        $query = RestaurantTable::with('activeOrder')->where('tenant_id', $tenantId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('label', 'like', "%{$filters['search']}%")
                  ->orWhere('table_number', $filters['search']);
            });
        }

        return $query;
    }
}
