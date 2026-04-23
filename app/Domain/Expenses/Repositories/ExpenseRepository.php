<?php

namespace App\Domain\Expenses\Repositories;

use App\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseRepository
{
    public function paginateForTenant(int $tenantId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Expense::with('user:id,name')
            ->where('tenant_id', $tenantId);

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('expense_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('expense_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('expense_date')->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $id): ?Expense
    {
        return Expense::with('user:id,name')
            ->where('tenant_id', $tenantId)
            ->find($id);
    }

    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update(array_filter($data, fn ($v) => $v !== null));
        return $expense->fresh('user:id,name');
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }
}
