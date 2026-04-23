<?php

namespace App\Domain\Employees\DTOs;

use App\Http\Requests\Employees\CreateEmployeeRequest;

readonly class CreateEmployeeDTO
{
    public function __construct(
        public int     $tenantId,
        public string  $name,
        public string  $email,
        public ?string $password,
        public ?string $pin,
        public string  $role,
    ) {}

    public static function fromRequest(CreateEmployeeRequest $request): self
    {
        return new self(
            tenantId: auth()->user()->tenant_id,
            name:     $request->validated('name'),
            email:    $request->validated('email'),
            password: $request->validated('password'),
            pin:      $request->validated('pin'),
            role:     $request->validated('role', 'cashier'),
        );
    }
}
