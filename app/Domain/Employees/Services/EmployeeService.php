<?php

namespace App\Domain\Employees\Services;

use App\Domain\Employees\DTOs\CreateEmployeeDTO;
use App\Domain\Employees\DTOs\UpdateEmployeeDTO;
use App\Domain\Employees\Repositories\EmployeeRepository;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\AuditLogger;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    use AuditLogger;

    public function __construct(private readonly EmployeeRepository $repo) {}

    public function create(CreateEmployeeDTO $dto, Tenant $tenant): User
    {
        if (! $tenant->canAddEmployee()) {
            throw ValidationException::withMessages([
                'employee' => ["Maximum employee limit ({$tenant->maxEmployees()}) reached for your plan."],
            ]);
        }

        $existing = User::where('email', $dto->email)->exists();
        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['This email is already in use.'],
            ]);
        }

        $employee = $this->repo->create([
            'tenant_id' => $dto->tenantId,
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => $dto->password,
            'pin'       => $dto->pin,
            'role'      => $dto->role,
            'is_active' => true,
        ]);

        $this->auditModel('created', $employee);

        return $employee;
    }

    public function update(User $employee, UpdateEmployeeDTO $dto): User
    {
        $old = $employee->toArray();

        $data = array_filter([
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => $dto->password,
            'pin'       => $dto->pin,
            'role'      => $dto->role,
            'is_active' => $dto->isActive,
        ], fn ($v) => $v !== null);

        if (isset($data['email']) && $data['email'] !== $employee->email) {
            $existing = User::where('email', $data['email'])->where('id', '!=', $employee->id)->exists();
            if ($existing) {
                throw ValidationException::withMessages(['email' => ['This email is already in use.']]);
            }
        }

        $updated = $this->repo->update($employee, $data);

        $this->auditModel('updated', $updated, $old);

        return $updated;
    }

    public function delete(User $employee): void
    {
        $this->audit('deleted', 'User', $employee->id, $employee->toArray());
        $this->repo->delete($employee);
    }

    public function setPin(User $employee, string $pin): User
    {
        $employee->update(['pin' => $pin]);
        $this->audit('pin_set', 'User', $employee->id);
        return $employee->fresh();
    }
}
