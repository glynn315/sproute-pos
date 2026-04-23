<?php

namespace App\Domain\Sales\Repositories;

use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SaleRepository
{
    public function paginateForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Sale::with(['user:id,name', 'items.product:id,name,cost_price'])
            ->where('tenant_id', $tenantId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $query->where('transaction_number', 'like', "%{$filters['search']}%");
        }

        return $query->latest()->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $saleId): ?Sale
    {
        return Sale::with(['user:id,name', 'items.product:id,name,cost_price'])
            ->where('tenant_id', $tenantId)
            ->find($saleId);
    }

    public function create(array $data): Sale
    {
        return Sale::create($data);
    }

    public function nextTransactionNumber(int $tenantId): string
    {
        $prefix = 'TXN-' . now()->format('Ymd') . '-';

        $last = Sale::where('tenant_id', $tenantId)
            ->where('transaction_number', 'like', "{$prefix}%")
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        $next = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
