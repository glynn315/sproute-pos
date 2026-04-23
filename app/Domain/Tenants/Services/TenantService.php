<?php

namespace App\Domain\Tenants\Services;

use App\Domain\Tenants\DTOs\UpdateTenantDTO;
use App\Domain\Tenants\Repositories\TenantRepository;
use App\Models\Tenant;
use App\Traits\AuditLogger;

class TenantService
{
    use AuditLogger;

    public function __construct(private readonly TenantRepository $repo) {}

    public function update(Tenant $tenant, UpdateTenantDTO $dto): Tenant
    {
        $old = $tenant->toArray();

        $updated = $this->repo->update($tenant, [
            'name'            => $dto->name,
            'phone'           => $dto->phone,
            'address'         => $dto->address,
            'logo_url'        => $dto->logoUrl,
            'primary_color'   => $dto->primaryColor,
            'secondary_color' => $dto->secondaryColor,
        ]);

        $this->auditModel('updated', $updated, $old);

        return $updated;
    }

    public function verify(Tenant $tenant): Tenant
    {
        $old = $tenant->only('status');
        $tenant->update(['status' => 'verified']);
        $this->audit('admin_verified', 'Tenant', $tenant->id, $old, ['status' => 'verified']);
        return $tenant->fresh();
    }

    public function suspend(Tenant $tenant): Tenant
    {
        $old = $tenant->only('status');
        $tenant->update(['status' => 'suspended']);
        $this->audit('suspended', 'Tenant', $tenant->id, $old, ['status' => 'suspended']);
        return $tenant->fresh();
    }

    public function assignSubscription(Tenant $tenant, int $planId, ?string $endsAt = null): Tenant
    {
        $old = $tenant->only('subscription_plan_id', 'subscription_ends_at');
        $tenant->update([
            'subscription_plan_id'  => $planId,
            'subscription_ends_at'  => $endsAt,
        ]);
        $this->audit('subscription_assigned', 'Tenant', $tenant->id, $old, $tenant->fresh()->toArray());
        return $tenant->fresh('subscriptionPlan');
    }
}
