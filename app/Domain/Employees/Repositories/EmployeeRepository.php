<?php

namespace App\Domain\Employees\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository
{
    public function allForTenant(int $tenantId): Collection
    {
        return User::where('tenant_id', $tenantId)
            ->whereIn('role', ['manager', 'cashier'])
            ->orderBy('name')
            ->get();
    }

    public function findForTenant(int $tenantId, int $userId): ?User
    {
        return User::where('tenant_id', $tenantId)
            ->whereIn('role', ['manager', 'cashier'])
            ->find($userId);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update(array_filter($data, fn ($v) => $v !== null));
        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $user->tokens()->delete();
        $user->refreshTokens()->delete();
        $user->delete();
    }

    public function countEmployees(int $tenantId): int
    {
        return User::where('tenant_id', $tenantId)
            ->whereIn('role', ['manager', 'cashier'])
            ->count();
    }
}
