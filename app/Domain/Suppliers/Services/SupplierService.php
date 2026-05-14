<?php

namespace App\Domain\Suppliers\Services;

use App\Domain\Suppliers\DTOs\CreateSupplierDTO;
use App\Domain\Suppliers\DTOs\UpdateSupplierDTO;
use App\Domain\Suppliers\Repositories\SupplierRepository;
use App\Models\Supplier;
use App\Traits\AuditLogger;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    use AuditLogger;

    public function __construct(private readonly SupplierRepository $repo) {}

    public function create(CreateSupplierDTO $dto): Supplier
    {
        $this->ensureNameUnique($dto->tenantId, $dto->name);

        $supplier = $this->repo->create([
            'tenant_id'    => $dto->tenantId,
            'name'         => $dto->name,
            'contact_name' => $dto->contactName,
            'phone'        => $dto->phone,
            'email'        => $dto->email,
            'address'      => $dto->address,
            'notes'        => $dto->notes,
            'is_active'    => true,
        ]);

        $this->auditModel('created', $supplier);

        return $supplier;
    }

    public function update(Supplier $supplier, UpdateSupplierDTO $dto): Supplier
    {
        $old = $supplier->toArray();

        if ($dto->name !== null) {
            $this->ensureNameUnique($supplier->tenant_id, $dto->name, $supplier->id);
        }

        $updated = $this->repo->update($supplier, [
            'name'         => $dto->name,
            'contact_name' => $dto->contactName,
            'phone'        => $dto->phone,
            'email'        => $dto->email,
            'address'      => $dto->address,
            'notes'        => $dto->notes,
            'is_active'    => $dto->isActive,
        ]);

        $this->auditModel('updated', $updated, $old);

        return $updated;
    }

    public function delete(Supplier $supplier): void
    {
        $this->audit('deleted', 'Supplier', $supplier->id, $supplier->toArray());
        $this->repo->delete($supplier);
    }

    private function ensureNameUnique(int $tenantId, string $name, ?int $excludeId = null): void
    {
        $query = Supplier::where('tenant_id', $tenantId)->where('name', $name);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => ['Supplier name already exists.']]);
        }
    }
}
