<?php

namespace App\Domain\Eatery\Payments\Repositories;

use App\Domain\Eatery\Payments\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository
{
    public function paginateForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Payment::with(['order.table:restaurant_table_id,table_number,label', 'cashier:id,name'])
            ->where('tenant_id', $tenantId);

        if (! empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        if (! empty($filters['order_id'])) {
            $query->where('order_id', (int) $filters['order_id']);
        }

        return $query->latest('payment_id')->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $paymentId): ?Payment
    {
        return Payment::with(['order.items', 'order.table', 'cashier:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('payment_id', $paymentId)
            ->first();
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }
}
