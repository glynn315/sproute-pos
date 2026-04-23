<?php

namespace App\Domain\Tenants\Repositories;

use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TenantRepository
{
    public function findById(int $id): ?Tenant
    {
        return Tenant::with('subscriptionPlan', 'owner')->find($id);
    }

    public function findByIdOrFail(int $id): Tenant
    {
        return Tenant::with('subscriptionPlan', 'owner')->findOrFail($id);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Tenant::with('subscriptionPlan', 'owner');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%"));
        }

        return $query->latest()->paginate($perPage);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update(array_filter($data, fn ($v) => $v !== null));
        return $tenant->fresh('subscriptionPlan');
    }
}
