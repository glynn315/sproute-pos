<?php

namespace App\Domain\Inventory\Repositories;

use App\Models\InventoryLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryRepository
{
    public function logsForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = InventoryLog::with(['product:id,name', 'user:id,name'])
            ->where('tenant_id', $tenantId);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
