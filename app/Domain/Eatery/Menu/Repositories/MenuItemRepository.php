<?php

namespace App\Domain\Eatery\Menu\Repositories;

use App\Domain\Eatery\Menu\Models\MenuItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MenuItemRepository
{
    public function listForTenant(int $tenantId, array $filters = []): Collection
    {
        return $this->buildQuery($tenantId, $filters)->orderBy('name')->get();
    }

    public function paginateForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->buildQuery($tenantId, $filters)->orderBy('name')->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $menuItemId): ?MenuItem
    {
        return MenuItem::where('tenant_id', $tenantId)
            ->where('menu_item_id', $menuItemId)
            ->first();
    }

    public function create(array $data): MenuItem
    {
        return MenuItem::create($data);
    }

    private function buildQuery(int $tenantId, array $filters)
    {
        $query = MenuItem::where('tenant_id', $tenantId);

        if (array_key_exists('availability', $filters) && $filters['availability'] !== null && $filters['availability'] !== '') {
            $query->where('availability', filter_var($filters['availability'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }
}
