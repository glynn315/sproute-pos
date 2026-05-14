<?php

namespace App\Domain\Eatery\Orders\Repositories;

use App\Domain\Eatery\Orders\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository
{
    public function paginateForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Order::with(['table:restaurant_table_id,table_number,label', 'cashier:id,name', 'items'])
            ->where('tenant_id', $tenantId);

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (! empty($filters['table_id'])) {
            $query->where('restaurant_table_id', (int) $filters['table_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['search'])) {
            $query->where('order_number', 'like', "%{$filters['search']}%");
        }

        return $query->latest('order_id')->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $orderId): ?Order
    {
        return Order::with(['table:restaurant_table_id,table_number,label', 'cashier:id,name', 'items', 'payment'])
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->first();
    }

    public function findUnpaidForTable(int $tenantId, int $restaurantTableId): ?Order
    {
        return Order::with(['items', 'table:restaurant_table_id,table_number,label'])
            ->where('tenant_id', $tenantId)
            ->where('restaurant_table_id', $restaurantTableId)
            ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
            ->latest('order_id')
            ->first();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function nextOrderNumber(int $tenantId): string
    {
        $prefix = 'ORD-' . now()->format('Ymd') . '-';
        $last = Order::where('tenant_id', $tenantId)
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByDesc('order_number')
            ->value('order_number');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
